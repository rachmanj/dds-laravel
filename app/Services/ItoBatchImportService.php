<?php

namespace App\Services;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\ItoBatchItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Smalot\PdfParser\Parser;

class ItoBatchImportService
{
    private const CONFIDENCE_THRESHOLD = 0.75;

    public function countPages(string $sourcePdfAbsolutePath): int
    {
        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($sourcePdfAbsolutePath);

        return max(0, $pageCount);
    }

    public function splitPage(string $sourcePdf, int $pageNumber): string
    {
        $absolutePath = $this->resolveAbsolutePath($sourcePdf);

        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($absolutePath);
        if ($pageNumber < 1 || $pageNumber > $pageCount) {
            throw new \InvalidArgumentException("Page {$pageNumber} is out of range (1-{$pageCount}).");
        }

        $pdf->AddPage();
        $pdf->useTemplate($pdf->importPage($pageNumber));

        $fileName = 'ito_batch_'.time().'_p'.$pageNumber.'_'.uniqid().'.pdf';
        $relativePath = 'ito-batch-pages/'.$fileName;
        $outputAbsolute = storage_path('app/public/'.$relativePath);

        $directory = dirname($outputAbsolute);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $pdf->Output('F', $outputAbsolute);

        return $relativePath;
    }

    /**
     * @return array{ito_no: string|null, confidence: float}
     */
    public function extractItoNumber(string $singlePagePdf): array
    {
        $absolutePath = $this->resolveAbsolutePath($singlePagePdf);

        $textResult = $this->extractFromTextLayer($absolutePath);
        if ($textResult !== null) {
            return $textResult;
        }

        return $this->extractFromOpenRouter($absolutePath);
    }

    public function normalizeNumber(?string $s): string
    {
        if ($s === null || $s === '') {
            return '';
        }

        return preg_replace('/[^A-Z0-9]/', '', strtoupper($s)) ?? '';
    }

    /**
     * @return array{status: string, document: ?AdditionalDocument, documents: Collection<int, AdditionalDocument>}
     */
    public function matchDocument(string $normalized): array
    {
        if ($normalized === '') {
            return [
                'status' => 'not_found',
                'document' => null,
                'documents' => collect(),
            ];
        }

        $itoTypeId = $this->itoTypeId();
        if (! $itoTypeId) {
            return [
                'status' => 'not_found',
                'document' => null,
                'documents' => collect(),
            ];
        }

        $candidates = AdditionalDocument::query()
            ->where('type_id', $itoTypeId)
            ->whereNotNull('document_number')
            ->get()
            ->filter(fn (AdditionalDocument $doc) => $this->normalizeNumber($doc->document_number) === $normalized)
            ->values();

        if ($candidates->isEmpty()) {
            return [
                'status' => 'not_found',
                'document' => null,
                'documents' => collect(),
            ];
        }

        if ($candidates->count() > 1) {
            return [
                'status' => 'ambiguous',
                'document' => null,
                'documents' => $candidates,
            ];
        }

        return [
            'status' => 'matched',
            'document' => $candidates->first(),
            'documents' => $candidates,
        ];
    }

    public function attachPage(ItoBatchItem $item, AdditionalDocument $document, int $batchNo): void
    {
        if (! $item->attached_path) {
            throw new \RuntimeException('Batch item has no split page to attach.');
        }

        $sourceAbsolute = storage_path('app/public/'.$item->attached_path);
        if (! is_file($sourceAbsolute)) {
            throw new \RuntimeException('Split page file not found on disk.');
        }

        $extension = pathinfo($sourceAbsolute, PATHINFO_EXTENSION) ?: 'pdf';
        $fileName = time().'_ito_'.$document->document_number.'.'.$extension;
        $destinationPath = 'attachments/'.$fileName;

        Storage::disk('public')->put($destinationPath, file_get_contents($sourceAbsolute));

        $document->update([
            'attachment' => $destinationPath,
            'batch_no' => $batchNo,
        ]);

        $item->update([
            'status' => 'matched',
            'matched_document_id' => $document->id,
            'resolved_at' => now(),
        ]);
    }

    public function nextBatchNo(): int
    {
        return (int) (AdditionalDocument::max('batch_no') ?? 0) + 1;
    }

    public function confidenceThreshold(): float
    {
        return self::CONFIDENCE_THRESHOLD;
    }

    public function isLowConfidence(?string $itoNo, float $confidence): bool
    {
        if ($itoNo === null || trim($itoNo) === '') {
            return true;
        }

        if ($confidence < self::CONFIDENCE_THRESHOLD) {
            return true;
        }

        $digits = preg_replace('/\D/', '', $itoNo);

        return $digits === null || strlen($digits) !== 9;
    }

    /**
     * @return array{ito_no: string|null, confidence: float}|null
     */
    private function extractFromTextLayer(string $absolutePath): ?array
    {
        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($absolutePath);
            $text = trim($pdf->getText());
        } catch (\Throwable $e) {
            return null;
        }

        if (mb_strlen($text) < 20) {
            return null;
        }

        if (preg_match('/\bNo\.?\s*(?!PO\b)(\d{9})\b/i', $text, $matches)) {
            return [
                'ito_no' => $matches[1],
                'confidence' => 0.95,
            ];
        }

        if (preg_match('/INVENTORY\s+TRANSFER\s+OUT/i', $text) && preg_match('/\b(\d{9})\b/', $text, $matches)) {
            return [
                'ito_no' => $matches[1],
                'confidence' => 0.85,
            ];
        }

        return null;
    }

    /**
     * @return array{ito_no: string|null, confidence: float}
     */
    private function extractFromOpenRouter(string $absolutePath): array
    {
        $key = config('services.openrouter.key');
        if (! $key) {
            throw new \RuntimeException('OpenRouter API key is not configured.');
        }

        $raw = file_get_contents($absolutePath);
        if ($raw === false || $raw === '') {
            throw new \RuntimeException('Could not read PDF file.');
        }

        $filename = basename($absolutePath) ?: 'ito-page.pdf';
        $dataUrl = 'data:application/pdf;base64,'.base64_encode($raw);
        $model = config('services.openrouter.batch_ocr_model') ?: config('services.openrouter.model');
        $engine = (string) config('services.openrouter.pdf_engine', 'mistral-ocr');

        $payload = [
            'model' => $model,
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $this->ocrSystemPrompt()],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Extract the ITO number from this INVENTORY TRANSFER OUT document page. Respond with JSON only as specified.',
                        ],
                        [
                            'type' => 'file',
                            'file' => [
                                'filename' => $filename,
                                'file_data' => $dataUrl,
                            ],
                        ],
                    ],
                ],
            ],
            'plugins' => [
                [
                    'id' => 'file-parser',
                    'pdf' => [
                        'engine' => $engine,
                    ],
                ],
            ],
        ];

        $timeout = (int) config('services.openrouter.batch_ocr_timeout', config('services.openrouter.timeout', 120));
        $decoded = $this->dispatchOpenRouterRequest($payload, $timeout);

        $itoNo = isset($decoded['ito_no']) ? (string) $decoded['ito_no'] : null;
        $confidence = isset($decoded['confidence']) ? (float) $decoded['confidence'] : 0.0;

        if ($itoNo === '') {
            $itoNo = null;
        }

        return [
            'ito_no' => $itoNo,
            'confidence' => $confidence,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dispatchOpenRouterRequest(array $payload, int $timeout): array
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
            Log::warning('ITO batch OCR OpenRouter HTTP error', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw new \RuntimeException('OpenRouter request failed: '.$response->status());
        }

        $body = $response->json();
        $content = $body['choices'][0]['message']['content'] ?? '';
        if (! is_string($content)) {
            throw new \RuntimeException('Unexpected OpenRouter response shape.');
        }

        $decoded = $this->decodeJsonContent($content);
        if ($decoded === null) {
            throw new \RuntimeException('Could not parse JSON from model response.');
        }

        return $decoded;
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

    private function ocrSystemPrompt(): string
    {
        return <<<'PROMPT'
You extract the ITO (Inventory Transfer Out) document number from scanned ITO forms.
The document title is "INVENTORY TRANSFER OUT".

Return ONLY a JSON object (no markdown) with exactly these keys:
- ito_no (string|null) the 9-digit ITO number from the field labeled "No." in the top-right header area (on the line under "No. PO"). Example: 261004859.
- confidence (number 0-1) your confidence in the extracted ito_no

CRITICAL rules:
- The ITO number is the value next to the label "No." — NOT "No. PO". "No. PO" is the purchase order number (a different 9-digit number, e.g. 260205412). Do NOT return the PO number as ito_no.
- The ITO number is always exactly 9 numeric digits.
- If you cannot read the "No." field clearly, set ito_no to null and use a low confidence score.
- Never invent or guess digits you cannot see.
PROMPT;
    }

    private function itoTypeId(): ?int
    {
        static $typeId = null;

        if ($typeId === null) {
            $typeId = AdditionalDocumentType::query()->where('type_name', 'ITO')->value('id');
        }

        return $typeId ? (int) $typeId : null;
    }

    private function resolveAbsolutePath(string $path): string
    {
        if (is_file($path)) {
            return $path;
        }

        $storagePath = storage_path('app/public/'.$path);
        if (is_file($storagePath)) {
            return $storagePath;
        }

        throw new \RuntimeException("PDF file not found: {$path}");
    }
}
