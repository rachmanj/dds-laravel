<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\SapApInvoicePayloadBuilder;
use App\Services\SapService;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateSapApInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300, 900]; // Exponential backoff in seconds

    protected $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function handle(SapService $sapService)
    {
        $invoice = $this->invoice->fresh(['supplier', 'type']);
        if (! $invoice) {
            Log::channel('sap')->error('CreateSapApInvoiceJob: Invoice reference could not be reloaded.');

            return;
        }

        $payload = null;

        try {
            // Validate supplier has SAP code
            if (! $invoice->supplier || ! $invoice->supplier->sap_code) {
                throw new \Exception('Supplier '.($invoice->supplier->name ?? ('#'.$invoice->supplier_id)).' does not have a SAP CardCode mapping.');
            }

            // Validate supplier exists in SAP
            $vendor = $this->resolveVendor($sapService, $invoice->supplier->sap_code);

            // Build payload using builder
            $payloadBuilder = new SapApInvoicePayloadBuilder($invoice);
            $payload = $payloadBuilder->build();

            // Create AP Invoice in SAP
            $response = $sapService->createApInvoice($payload);

            DB::transaction(function () use ($invoice, $response, $payload) {
                $invoice->update([
                    'sap_status' => 'posted',
                    'sap_doc_num' => $response['DocNum'] ?? null,
                    'sap_error_message' => null,
                    'sap_last_attempted_at' => now(),
                ]);

                DB::table('sap_logs')->insert([
                    'invoice_id' => $invoice->id,
                    'action' => 'create_invoice',
                    'status' => 'success',
                    'request_payload' => json_encode($payload),
                    'response_payload' => json_encode($response),
                    'attempt_count' => $this->attempts(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            Log::channel('sap')->info('AP Invoice created successfully', [
                'invoice_id' => $invoice->id,
                'sap_doc_num' => $response['DocNum'] ?? null,
            ]);
        } catch (\Exception $e) {
            $invoice->update([
                'sap_status' => 'failed',
                'sap_error_message' => $e->getMessage(),
                'sap_last_attempted_at' => now(),
            ]);

            DB::table('sap_logs')->insert([
                'invoice_id' => $invoice->id,
                'action' => 'create_invoice',
                'status' => 'failed',
                'request_payload' => json_encode($payload ?? []),
                'response_payload' => null,
                'error_message' => $e->getMessage(),
                'attempt_count' => $this->attempts(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::channel('sap')->error('AP Invoice creation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Retry if not max attempts
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1]);
            } else {
                Log::channel('sap')->error('Max retries exceeded for invoice '.$invoice->id);
            }

            throw $e;
        }
    }

    protected function resolveVendor(SapService $sapService, string $cardCode): array
    {
        try {
            $vendor = $sapService->getBusinessPartner($cardCode);
        } catch (RequestException $exception) {
            $parsedMessage = $this->parseSapErrorMessage($exception);
            throw new \Exception("SAP vendor {$cardCode} not found. {$parsedMessage}");
        }

        if (! $vendor || empty($vendor['CardCode'])) {
            throw new \Exception("SAP vendor {$cardCode} not found.");
        }

        $cardType = strtolower($vendor['CardType'] ?? '');
        if (! in_array($cardType, ['s', 'csupplier'], true)) {
            $cardTypeLabel = $vendor['CardType'] ?? 'unknown';
            throw new \Exception("SAP Business Partner {$cardCode} has CardType '{$cardTypeLabel}'. Expected supplier.");
        }

        return $vendor;
    }

    protected function parseSapErrorMessage(RequestException $exception): string
    {
        $response = $exception->getResponse();

        if (! $response) {
            return $exception->getMessage();
        }

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        if (isset($decoded['error']['message']['value'])) {
            return $decoded['error']['message']['value'];
        }

        return $body ?: $exception->getMessage();
    }
}
