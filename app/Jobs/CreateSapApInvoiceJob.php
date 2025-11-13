<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\SapService;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
        try {
            // Validate vendor
            $vendor = $sapService->getBusinessPartner($this->invoice->supplier->sap_code ?? $this->invoice->supplier->code);
            if (!$vendor || $vendor['CardType'] !== 'S') {
                throw new \Exception('Invalid vendor CardCode');
            }

            // Build payload (map from invoice data)
            $payload = [
                'CardCode' => $vendor['CardCode'],
                'DocDate' => $this->invoice->invoice_date->format('Y-m-d'),
                'DocDueDate' => $this->invoice->payment_date ? $this->invoice->payment_date->format('Y-m-d') : now()->addDays(30)->format('Y-m-d'),
                'Comments' => $this->invoice->remarks ?? 'Imported from DDS - Invoice #' . $this->invoice->id,
                'DocumentLines' => [ // Assume single line for simplicity; expand if needed
                    [
                        'Quantity' => 1,
                        'UnitPrice' => $this->invoice->amount,
                        'TaxCode' => 'EXEMPT', // Configure in config if needed
                    ]
                ],
                // Add more fields as per your mapping (e.g., PO reference, project codes)
            ];

            $response = $sapService->createApInvoice($payload);

            DB::transaction(function () use ($response) {
                $this->invoice->update([
                    'sap_status' => 'posted',
                    'sap_doc_num' => $response['DocNum'],
                    'sap_last_attempted_at' => now(),
                ]);

                DB::table('sap_logs')->insert([
                    'invoice_id' => $this->invoice->id,
                    'action' => 'create_invoice',
                    'status' => 'success',
                    'request_payload' => json_encode($payload),
                    'response_payload' => json_encode($response),
                    'attempt_count' => $this->attemptCount(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (\Exception $e) {
            $this->invoice->update([
                'sap_status' => 'failed',
                'sap_error_message' => $e->getMessage(),
                'sap_last_attempted_at' => now(),
            ]);

            DB::table('sap_logs')->insert([
                'invoice_id' => $this->invoice->id,
                'action' => 'create_invoice',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'attempt_count' => $this->attemptCount(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1]);
            } else {
                Log::channel('sap')->error('Max retries exceeded for invoice ' . $this->invoice->id);
            }
        }
    }
}
