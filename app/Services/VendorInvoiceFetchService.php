<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLineDetail;
use App\Models\Supplier;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VendorInvoiceFetchService
{
    public static function parseInvoiceNumbers(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);

        if ($parts === false) {
            return [];
        }

        $unique = [];
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ($trimmed !== '') {
                $unique[$trimmed] = true;
            }
        }

        return array_keys($unique);
    }

    /**
     * @return array{config: array<string, mixed>, supplier: Supplier}
     */
    public function resolveVendorContext(string $sapCode): array
    {
        $vendors = config('vendor_api.vendors', []);
        if (! isset($vendors[$sapCode]) || ! is_array($vendors[$sapCode])) {
            abort(404);
        }

        $config = $vendors[$sapCode];
        if (empty($config['base_url']) || empty($config['token'])) {
            throw new RuntimeException('Vendor API credentials are not configured. Set the VENDOR_*_API_URL and VENDOR_*_API_TOKEN environment variables.');
        }

        $supplier = Supplier::query()->where('sap_code', $sapCode)->firstOrFail();

        return ['config' => $config, 'supplier' => $supplier];
    }

    /**
     * @return list<array{invoice_no: string, status: string, data: ?array, lines: ?array, error: ?string}>
     */
    public function lookup(string $sapCode, array $invoiceNos): array
    {
        ['config' => $config, 'supplier' => $supplier] = $this->resolveVendorContext($sapCode);
        $baseUrl = rtrim((string) $config['base_url'], '/');

        $results = [];
        foreach ($invoiceNos as $invoiceNo) {
            $results[] = $this->lookupOne($baseUrl, $config, $supplier->id, $invoiceNo);
        }

        return $results;
    }

    /**
     * @return array{imported: int, skipped: int, errors: list<string>}
     */
    public function importSelected(string $sapCode, array $invoiceNos, int $createdByUserId, ?string $importerDepartmentLocation = null): array
    {
        ['config' => $config, 'supplier' => $supplier] = $this->resolveVendorContext($sapCode);
        $baseUrl = rtrim((string) $config['base_url'], '/');
        $curLoc = $this->resolveImporterCurLoc($config, $importerDepartmentLocation);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($invoiceNos as $invoiceNo) {
            $invoiceNo = trim((string) $invoiceNo);
            if ($invoiceNo === '') {
                continue;
            }

            if (Invoice::query()->where('supplier_id', $supplier->id)->where('invoice_number', $invoiceNo)->exists()) {
                $skipped++;

                continue;
            }

            $payload = $this->fetchInvoiceDetail($baseUrl, $config, $invoiceNo);
            if ($payload === null) {
                $errors[] = "Could not fetch invoice {$invoiceNo} from vendor API.";

                continue;
            }

            try {
                $this->persistInvoice($supplier, $config, $payload, $createdByUserId, $curLoc);
                $imported++;
            } catch (\Throwable $e) {
                Log::error('Vendor invoice import failed', [
                    'invoice_no' => $invoiceNo,
                    'supplier_id' => $supplier->id,
                    'exception' => $e->getMessage(),
                ]);
                $errors[] = 'Import failed for '.$invoiceNo.': '.$e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveImporterCurLoc(array $config, ?string $importerDepartmentLocation): ?string
    {
        if ($importerDepartmentLocation !== null && trim($importerDepartmentLocation) !== '') {
            return trim($importerDepartmentLocation);
        }

        $fallback = $config['cur_loc'] ?? null;

        if ($fallback !== null && (string) $fallback !== '') {
            return (string) $fallback;
        }

        return null;
    }

    /**
     * @return array{invoice_no: string, status: string, data: ?array, lines: ?array, error: ?string}
     */
    private function lookupOne(string $baseUrl, array $vendorConfig, int $supplierId, string $invoiceNo): array
    {
        $payload = $this->fetchInvoiceDetail($baseUrl, $vendorConfig, $invoiceNo);
        if ($payload === null) {
            return [
                'invoice_no' => $invoiceNo,
                'status' => 'not_found',
                'data' => null,
                'lines' => null,
                'error' => null,
            ];
        }

        if (Invoice::query()->where('supplier_id', $supplierId)->where('invoice_number', $invoiceNo)->exists()) {
            return [
                'invoice_no' => $invoiceNo,
                'status' => 'duplicate',
                'data' => $payload,
                'lines' => $payload['lines'] ?? [],
                'error' => null,
            ];
        }

        return [
            'invoice_no' => $invoiceNo,
            'status' => 'new',
            'data' => $payload,
            'lines' => $payload['lines'] ?? [],
            'error' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $vendorConfig  Single vendor entry from config('vendor_api.vendors.*')
     * @return ?array<string, mixed>
     */
    private function fetchInvoiceDetail(string $baseUrl, array $vendorConfig, string $invoiceNo): ?array
    {
        $path = '/api/v1/invoices/'.rawurlencode($invoiceNo);
        $url = $baseUrl.$path;

        try {
            $response = $this->vendorHttp($vendorConfig)->get($url);
        } catch (\Throwable $e) {
            Log::warning('Vendor invoice API request failed', [
                'invoice_no' => $invoiceNo,
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            $this->logUnsuccessfulVendorResponse($invoiceNo, $url, $response);

            return null;
        }

        $payload = $this->extractInvoiceDetailPayload($response);
        if ($payload === null) {
            Log::warning('Vendor invoice API response shape not recognized', [
                'invoice_no' => $invoiceNo,
                'url' => $url,
                'content_type' => $response->header('Content-Type'),
                'body_preview' => mb_substr($response->body(), 0, 800),
            ]);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $vendorConfig
     */
    private function vendorHttp(array $vendorConfig): PendingRequest
    {
        $request = Http::timeout(60)
            ->acceptJson()
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => (string) config('vendor_api.http.user_agent', 'DDS-Laravel-VendorInvoice/1.0'),
            ])
            ->withToken((string) $vendorConfig['token']);

        if (config('vendor_api.http.verify_ssl', true) === false) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }

    private function logUnsuccessfulVendorResponse(string $invoiceNo, string $url, Response $response): void
    {
        Log::warning('Vendor invoice API non-success response', [
            'invoice_no' => $invoiceNo,
            'url' => $url,
            'status' => $response->status(),
            'content_type' => $response->header('Content-Type'),
            'body_preview' => mb_substr($response->body(), 0, 800),
        ]);
    }

    /**
     * @return ?array<string, mixed>
     */
    private function extractInvoiceDetailPayload(Response $response): ?array
    {
        $json = $response->json();

        if (! is_array($json)) {
            return null;
        }

        $candidate = null;
        if (isset($json['data']) && is_array($json['data'])) {
            $candidate = $json['data'];
        } elseif (isset($json['invoice_no'])) {
            $candidate = $json;
        }

        if (! is_array($candidate) || ! isset($candidate['invoice_no'])) {
            return null;
        }

        return $candidate;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $payload
     */
    private function persistInvoice(Supplier $supplier, array $config, array $payload, int $createdByUserId, ?string $curLoc): void
    {
        $invoiceNo = (string) ($payload['invoice_no'] ?? '');
        $currency = '';
        if (isset($payload['currency']) && is_array($payload['currency'])) {
            $currency = (string) ($payload['currency']['code'] ?? '');
        }
        if ($currency === '') {
            $currency = 'IDR';
        }

        $referenceNo = isset($payload['reference_no']) ? (string) $payload['reference_no'] : '';
        $poNo = $referenceNo !== '' ? mb_substr($referenceNo, 0, 30) : null;

        $lines = [];
        if (isset($payload['lines']) && is_array($payload['lines'])) {
            $lines = $payload['lines'];
        }

        DB::transaction(function () use ($supplier, $config, $payload, $invoiceNo, $currency, $poNo, $lines, $createdByUserId, $curLoc): void {
            $invoice = Invoice::query()->create([
                'invoice_number' => $invoiceNo,
                'faktur_no' => null,
                'invoice_date' => $payload['date'] ?? now()->toDateString(),
                'receive_date' => now()->toDateString(),
                'supplier_id' => $supplier->id,
                'po_no' => $poNo,
                'receive_project' => null,
                'invoice_project' => null,
                'payment_project' => $supplier->payment_project,
                'currency' => $currency,
                'amount' => $payload['total_amount'] ?? 0,
                'type_id' => (int) $config['type_id'],
                'payment_date' => null,
                'remarks' => isset($payload['description']) ? (string) $payload['description'] : null,
                'cur_loc' => $curLoc,
                'status' => 'open',
                'created_by' => $createdByUserId,
                'import_extraction' => null,
            ]);

            $rows = [];
            foreach ($lines as $index => $line) {
                if (! is_array($line)) {
                    continue;
                }
                $description = '';
                if (isset($line['item'])) {
                    $description = (string) $line['item'];
                } elseif (isset($line['description'])) {
                    $description = (string) $line['description'];
                }
                $rows[] = [
                    'invoice_id' => $invoice->id,
                    'line_no' => $index + 1,
                    'description' => $description !== '' ? $description : '-',
                    'quantity' => isset($line['qty']) ? $line['qty'] : null,
                    'unit_price' => isset($line['unit_price']) ? $line['unit_price'] : null,
                    'amount' => isset($line['total']) ? $line['total'] : null,
                    'source' => 'vendor_api',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($rows !== []) {
                InvoiceLineDetail::query()->insert($rows);
            }
        });
    }
}
