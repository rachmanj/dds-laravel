<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class SignatureMatchingService
{
    /**
     * @param  array<int, array{id: int, name: string, image_path: string}>  $candidates
     * @return array<int, array{specimen_id: int, score: float|null, verdict: string, reasoning: string|null, document_crop: string|null, specimen_crop: string|null, raw_response: string|null}>
     */
    public function matchCandidates(string $documentAbsolutePath, string $documentMime, array $candidates): array
    {
        if (empty($candidates)) {
            return [];
        }

        $documentCropDataUrl = $this->buildDocumentCropDataUrl($documentAbsolutePath, $documentMime);
        $results = [];

        foreach ($candidates as $candidate) {
            $results[] = $this->matchSingleCandidate(
                [
                    'type' => 'image',
                    'content' => $documentCropDataUrl,
                ],
                $documentMime,
                $candidate
            );
        }

        return $results;
    }

    /**
     * @param  array{type?: string, content: string}  $documentPayload
     * @param  array{id: int, name: string, image_path: string}  $candidate
     * @return array{specimen_id: int, score: float|null, verdict: string, reasoning: string|null, document_crop: string|null, specimen_crop: string|null, raw_response: string|null}
     */
    public function matchSingleCandidate(array $documentPayload, string $documentMime, array $candidate): array
    {
        $specimenDataUrl = $this->fileToDataUrl($candidate['image_path']);

        try {
            $decoded = $this->callOpenRouter($documentPayload, $specimenDataUrl);
        } catch (\Throwable $e) {
            Log::warning('Signature matching API call failed', [
                'specimen_id' => $candidate['id'],
                'message' => $e->getMessage(),
            ]);

            return [
                'specimen_id' => $candidate['id'],
                'score' => null,
                'verdict' => 'uncertain',
                'reasoning' => 'AI request failed: '.$e->getMessage(),
                'document_crop' => null,
                'specimen_crop' => null,
                'raw_response' => null,
            ];
        }

        if ($decoded === null) {
            return [
                'specimen_id' => $candidate['id'],
                'score' => null,
                'verdict' => 'uncertain',
                'reasoning' => 'Could not parse JSON from model response.',
                'document_crop' => null,
                'specimen_crop' => null,
                'raw_response' => null,
            ];
        }

        $score = isset($decoded['score']) ? (float) $decoded['score'] : null;
        $verdict = $this->resolveVerdict($score, $decoded['verdict'] ?? null);

        return [
            'specimen_id' => $candidate['id'],
            'score' => $score,
            'verdict' => $verdict,
            'reasoning' => isset($decoded['reasoning']) ? (string) $decoded['reasoning'] : null,
            'document_crop' => isset($decoded['document_signature_crop']) ? (string) $decoded['document_signature_crop'] : null,
            'specimen_crop' => isset($decoded['specimen_signature_crop']) ? (string) $decoded['specimen_signature_crop'] : null,
            'raw_response' => json_encode($decoded),
        ];
    }

    public function resolveVerdict(?float $score, ?string $modelVerdict): string
    {
        if ($modelVerdict === 'no_match') {
            return 'no_match';
        }

        if ($score === null) {
            return 'uncertain';
        }

        $matchThreshold = (float) config('services.openrouter.signature_match_threshold', 0.75);
        $uncertainThreshold = (float) config('services.openrouter.signature_uncertain_threshold', 0.45);

        if ($score >= $matchThreshold) {
            return 'matched';
        }

        if ($score >= $uncertainThreshold) {
            return 'uncertain';
        }

        return 'no_match';
    }

    private function buildDocumentCropDataUrl(string $absolutePath, string $mime): string
    {
        $bytes = $this->readDocumentImageBytes($absolutePath, $mime);

        return $this->cropBottomRegionToJpegDataUrl($bytes);
    }

    private function readDocumentImageBytes(string $absolutePath, string $mime): string
    {
        if ($mime === 'application/pdf' || str_ends_with(strtolower($absolutePath), '.pdf')) {
            return $this->extractPdfEmbeddedImage($absolutePath);
        }

        $bytes = file_get_contents($absolutePath);
        if ($bytes === false || $bytes === '') {
            throw new \RuntimeException('Could not read document image: '.$absolutePath);
        }

        return $bytes;
    }

    private function extractPdfEmbeddedImage(string $absolutePath): string
    {
        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($absolutePath);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not parse PDF for signature matching: '.$e->getMessage(), 0, $e);
        }

        $images = array_values($pdf->getObjectsByType('XObject', 'Image'));
        $image = $images[0] ?? null;
        $bytes = $image?->getContent();

        if (! is_string($bytes) || $bytes === '') {
            throw new \RuntimeException('Could not extract an embedded page image from the PDF. Signature matching requires a scanned PDF.');
        }

        return $bytes;
    }

    private function cropBottomRegionToJpegDataUrl(string $bytes): string
    {
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw new \RuntimeException('Could not decode document image for signature matching.');
        }

        $cropped = $this->cropBottomRegion($image);

        ob_start();
        $encoded = imagejpeg($cropped, null, 85);
        $jpeg = ob_get_clean();

        if ($cropped !== $image) {
            imagedestroy($cropped);
        }
        imagedestroy($image);

        if ($encoded === false || ! is_string($jpeg) || $jpeg === '') {
            throw new \RuntimeException('Could not encode signature crop as JPEG.');
        }

        return 'data:image/jpeg;base64,'.base64_encode($jpeg);
    }

    private function cropBottomRegion(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = (float) config('services.openrouter.signature_crop_bottom_ratio', 0.30);
        if ($ratio <= 0.0 || $ratio > 1.0) {
            $ratio = 0.30;
        }

        $cropY = (int) ($height * (1 - $ratio));
        $cropHeight = $height - $cropY;
        if ($cropHeight < 1 || $cropY < 0) {
            return $image;
        }

        $cropped = imagecrop($image, [
            'x' => 0,
            'y' => $cropY,
            'width' => $width,
            'height' => $cropHeight,
        ]);

        if ($cropped instanceof \GdImage) {
            return $cropped;
        }

        $manual = imagecreatetruecolor($width, $cropHeight);
        if ($manual === false) {
            return $image;
        }

        imagecopy($manual, $image, 0, 0, 0, $cropY, $width, $cropHeight);

        return $manual;
    }

    /**
     * @param  array{type?: string, content: string}  $documentPayload
     * @return array<string, mixed>|null
     */
    private function callOpenRouter(array $documentPayload, string $specimenDataUrl): ?array
    {
        $key = config('services.openrouter.key');
        if (! $key) {
            throw new \RuntimeException('OpenRouter API key is not configured.');
        }

        $documentDataUrl = $documentPayload['content'];
        if (! is_string($documentDataUrl) || $documentDataUrl === '') {
            throw new \RuntimeException('Document crop image is missing.');
        }

        $payload = [
            'model' => $this->signatureModel(),
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $this->userPrompt(),
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => ['url' => $documentDataUrl],
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => ['url' => $specimenDataUrl],
                        ],
                    ],
                ],
            ],
        ];

        $timeout = (int) config('services.openrouter.signature_timeout', 180);
        $response = $this->dispatchRequest($payload, $timeout);
        $decoded = $this->decodeJsonContent($response);

        if ($decoded === null) {
            $response = $this->dispatchRequest($payload, $timeout);
            $decoded = $this->decodeJsonContent($response);
        }

        return $decoded;
    }

    private function signatureModel(): string
    {
        $model = config('services.openrouter.signature_model');

        return filled($model) ? (string) $model : 'google/gemini-2.5-flash';
    }

    private function userPrompt(): string
    {
        return 'Compare the handwritten signature in the "Received by" area of the document (first image) with the specimen signature (second image). Are they written by the same person? Ignore stamps/seals and printed names. Reply ONLY a JSON object: {"score": <number 0-1>, "verdict": "matched"|"uncertain"|"no_match", "reasoning": "<brief>"}';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function dispatchRequest(array $payload, int $timeout): string
    {
        $baseUrl = rtrim((string) config('services.openrouter.base_url'), '/');
        $url = $baseUrl.'/chat/completions';

        $pending = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer '.config('services.openrouter.key'),
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name', 'DDS'),
            ]);

        $caBundle = config('services.openrouter.ca_bundle');
        if (filled($caBundle) && is_string($caBundle) && is_file($caBundle)) {
            $pending = $pending->withOptions(['verify' => $caBundle]);
        }

        $response = $pending->acceptJson()->post($url, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenRouter request failed: '.$response->status());
        }

        $body = $response->json();
        $content = $body['choices'][0]['message']['content'] ?? '';
        if (! is_string($content)) {
            throw new \RuntimeException('Unexpected OpenRouter response shape.');
        }

        return $content;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonContent(string $content): ?array
    {
        $content = trim($content);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        if (preg_match('/\{[\s\S]*\}/', $content, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function fileToDataUrl(string $absolutePath, ?string $mime = null): string
    {
        $contents = file_get_contents($absolutePath);
        if ($contents === false) {
            throw new \RuntimeException('Could not read file: '.$absolutePath);
        }

        if ($mime === null) {
            $mime = match (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION))) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                default => 'image/jpeg',
            };
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
