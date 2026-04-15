<?php

namespace App\Services;

use App\Data\InvoiceExtractionResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class OpenRouterInvoiceExtractionService
{
    public function extractFromImage(string $absolutePath, string $mime): InvoiceExtractionResult
    {
        $contents = file_get_contents($absolutePath);
        if ($contents === false) {
            throw new \RuntimeException('Could not read image file.');
        }
        $base64 = base64_encode($contents);
        $dataUrl = 'data:'.$mime.';base64,'.$base64;

        return $this->callOpenRouterVision($dataUrl);
    }

    /**
     * Prefer embedded PDF text (fast). If the PDF is image-only (scan), send the file to OpenRouter
     * using the file-parser plugin (see https://openrouter.ai/docs/guides/overview/multimodal/pdfs).
     */
    public function extractFromPdf(string $absolutePath): InvoiceExtractionResult
    {
        $parser = new Parser;
        $pdf = $parser->parseFile($absolutePath);

        if (config('services.openrouter.pdf_first_page_only', true)) {
            $text = '';
            foreach ($pdf->getPages() as $page) {
                $text = trim($page->getText());
                break;
            }
        } else {
            $text = trim($pdf->getText());
        }

        if (mb_strlen($text) >= 80) {
            $text = mb_substr($text, 0, 12000);

            return $this->callOpenRouterText($text);
        }

        return $this->callOpenRouterPdfFile($absolutePath);
    }

    private function callOpenRouterPdfFile(string $absolutePath): InvoiceExtractionResult
    {
        $key = config('services.openrouter.key');
        if (! $key) {
            throw new \RuntimeException('OpenRouter API key is not configured.');
        }

        $pathForRead = $absolutePath;
        $tempFirstPage = null;
        if (config('services.openrouter.pdf_first_page_only', true)) {
            $tempFirstPage = app(PdfInvoiceFirstPageService::class)->tempFileFirstPageOnly($absolutePath);
            if ($tempFirstPage !== null) {
                $pathForRead = $tempFirstPage;
            }
        }

        try {
            $raw = file_get_contents($pathForRead);
        } finally {
            app(PdfInvoiceFirstPageService::class)->deleteTemp($tempFirstPage);
        }

        if ($raw === false || $raw === '') {
            throw new \RuntimeException('Could not read PDF file.');
        }

        $filename = basename($pathForRead) ?: 'invoice.pdf';
        $dataUrl = 'data:application/pdf;base64,'.base64_encode($raw);

        $engine = (string) config('services.openrouter.pdf_engine', 'mistral-ocr');
        $payload = [
            'model' => config('services.openrouter.model'),
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Extract invoice fields from this PDF document (may be a scanned image). Respond with JSON only as specified.',
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

        $timeout = (int) config('services.openrouter.pdf_timeout', 240);

        return $this->dispatchRequest($payload, $timeout);
    }

    private function callOpenRouterVision(string $dataUrl): InvoiceExtractionResult
    {
        $key = config('services.openrouter.key');
        if (! $key) {
            throw new \RuntimeException('OpenRouter API key is not configured.');
        }

        $payload = [
            'model' => config('services.openrouter.model'),
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'Extract invoice fields from this image. Respond with JSON only as specified.'],
                        ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                    ],
                ],
            ],
        ];

        return $this->dispatchRequest($payload);
    }

    private function callOpenRouterText(string $invoiceText): InvoiceExtractionResult
    {
        $key = config('services.openrouter.key');
        if (! $key) {
            throw new \RuntimeException('OpenRouter API key is not configured.');
        }

        $payload = [
            'model' => config('services.openrouter.model'),
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                [
                    'role' => 'user',
                    'content' => "Extract invoice fields from the following document text.\n\n---\n".$invoiceText."\n---",
                ],
            ],
        ];

        return $this->dispatchRequest($payload);
    }

    private function dispatchRequest(array $payload, ?int $timeoutSeconds = null): InvoiceExtractionResult
    {
        $baseUrl = rtrim((string) config('services.openrouter.base_url'), '/');
        $url = $baseUrl.'/chat/completions';

        $timeout = $timeoutSeconds ?? (int) config('services.openrouter.timeout', 120);

        $pending = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer '.config('services.openrouter.key'),
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name', 'DDS'),
            ]);

        $caBundle = config('services.openrouter.ca_bundle');
        if (filled($caBundle)) {
            if (! is_string($caBundle) || ! is_file($caBundle)) {
                Log::channel('invoice_import')->warning('OpenRouter CA bundle path is missing or not readable', [
                    'path' => $caBundle,
                ]);
            } else {
                $pending = $pending->withOptions(['verify' => $caBundle]);
            }
        }

        $response = $pending->acceptJson()->post($url, $payload);

        if (! $response->successful()) {
            Log::channel('invoice_import')->warning('OpenRouter HTTP error', [
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
            Log::channel('invoice_import')->warning('OpenRouter JSON parse failed', [
                'snippet' => mb_substr($content, 0, 400),
            ]);
            throw new \RuntimeException('Could not parse JSON from model response.');
        }

        return InvoiceExtractionResult::fromOpenRouterArray($decoded);
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

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You extract supplier invoice header data for accounting entry.
Return ONLY a JSON object (no markdown) with exactly these keys:
- invoice_number (string|null)
- faktur_no (string|null) tax invoice / NPWP faktur if visible
- invoice_date (string|null) as YYYY-MM-DD
- receive_date (string|null) as YYYY-MM-DD if shown, else null
- supplier_name (string|null) vendor name as printed
- supplier_tax_id (string|null) tax ID / NPWP if visible
- po_no (string|null) purchase order reference
- currency (string|null) 3-letter ISO code (IDR, USD, etc.)
- amount (number|null) total payable amount as a decimal number
- line_items (array of objects with description string and amount number|null) optional
- confidence (number 0-1) your confidence in amount and supplier_name
- warnings (array of strings) any issues
- low_confidence_fields (array of strings) field names where values are uncertain

If a value is missing or unreadable, use null. Never invent amounts.
PROMPT;
    }
}
