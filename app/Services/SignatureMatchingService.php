<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        $documentPayload = $this->buildDocumentContent($documentAbsolutePath, $documentMime);
        $results = [];

        foreach ($candidates as $candidate) {
            $results[] = $this->matchSingleCandidate($documentPayload, $documentMime, $candidate);
        }

        return $results;
    }

    /**
     * @param  array{id: int, name: string, image_path: string}  $candidate
     * @return array{specimen_id: int, score: float|null, verdict: string, reasoning: string|null, document_crop: string|null, specimen_crop: string|null, raw_response: string|null}
     */
    public function matchSingleCandidate(array $documentPayload, string $documentMime, array $candidate): array
    {
        $specimenDataUrl = $this->fileToDataUrl($candidate['image_path']);

        try {
            $decoded = $this->callOpenRouter($documentPayload, $documentMime, $specimenDataUrl, $candidate['name']);
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

    /**
     * @return array{type: string, content: mixed}
     */
    private function buildDocumentContent(string $absolutePath, string $mime): array
    {
        if ($mime === 'application/pdf' || str_ends_with(strtolower($absolutePath), '.pdf')) {
            $raw = file_get_contents($absolutePath);
            if ($raw === false) {
                throw new \RuntimeException('Could not read PDF file.');
            }

            return [
                'type' => 'pdf',
                'content' => [
                    'filename' => basename($absolutePath) ?: 'document.pdf',
                    'data_url' => 'data:application/pdf;base64,'.base64_encode($raw),
                ],
            ];
        }

        return [
            'type' => 'image',
            'content' => $this->fileToDataUrl($absolutePath, $mime),
        ];
    }

    /**
     * @param  array{type: string, content: mixed}  $documentPayload
     * @return array<string, mixed>
     */
    private function callOpenRouter(array $documentPayload, string $documentMime, string $specimenDataUrl, string $specimenName): array
    {
        $key = config('services.openrouter.key');
        if (! $key) {
            throw new \RuntimeException('OpenRouter API key is not configured.');
        }

        $userContent = [
            [
                'type' => 'text',
                'text' => 'Compare the handwritten signature on the document with the specimen signature for "'.$specimenName.'". Respond with JSON only as specified.',
            ],
        ];

        if ($documentPayload['type'] === 'pdf') {
            $userContent[] = [
                'type' => 'file',
                'file' => [
                    'filename' => $documentPayload['content']['filename'],
                    'file_data' => $documentPayload['content']['data_url'],
                ],
            ];
        } else {
            $userContent[] = [
                'type' => 'image_url',
                'image_url' => ['url' => $documentPayload['content']],
            ];
        }

        $userContent[] = [
            'type' => 'image_url',
            'image_url' => ['url' => $specimenDataUrl],
        ];

        $model = config('services.openrouter.signature_model') ?: config('services.openrouter.model');

        $payload = [
            'model' => $model,
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user', 'content' => $userContent],
            ],
        ];

        if ($documentPayload['type'] === 'pdf') {
            $engine = (string) config('services.openrouter.pdf_engine', 'mistral-ocr');
            $payload['plugins'] = [
                [
                    'id' => 'file-parser',
                    'pdf' => ['engine' => $engine],
                ],
            ];
        }

        $timeout = (int) config('services.openrouter.signature_timeout', config('services.openrouter.timeout', 120));
        $response = $this->dispatchRequest($payload, $timeout);
        $decoded = $this->decodeJsonContent($response);

        if ($decoded === null) {
            throw new \RuntimeException('Could not parse JSON from model response.');
        }

        return $decoded;
    }

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

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a forensic handwriting analyst assisting accounting staff to compare handwritten signatures.
Return ONLY a JSON object (no markdown) with exactly these keys:
- score (number 0-1) confidence that the document signature was made by the same person as the specimen
- verdict (string) one of: matched, uncertain, no_match
- reasoning (string) brief explanation
- document_signature_crop (string|null) base64 data URL of the cropped handwritten signature region from the document, or null if not found
- specimen_signature_crop (string|null) base64 data URL of the cropped signature from the specimen image, or null

Rules:
- Compare HANDWRITTEN signatures only. Ignore stamps/seals (cap/stempel), printed names, and logos.
- If you cannot find a handwritten signature on the document, set score to 0, verdict to no_match, and explain why.
- If unsure, use verdict uncertain and a conservative score. Do not guess a person's name when uncertain.
- Use no_match when the signatures clearly belong to different people or when no valid signature is visible.
PROMPT;
    }
}
