<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\SapService;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelSapApInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300, 900];

    protected Invoice $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function handle(SapService $sapService): void
    {
        $invoice = $this->invoice->fresh();
        if (! $invoice) {
            Log::channel('sap')->error('CancelSapApInvoiceJob: Invoice reference could not be reloaded.');

            return;
        }

        if ($invoice->sap_status === 'cancelled') {
            Log::channel('sap')->info('CancelSapApInvoiceJob: Invoice already cancelled in SAP, skipping.', [
                'invoice_id' => $invoice->id,
                'sap_cancellation_doc_num' => $invoice->sap_cancellation_doc_num,
            ]);

            return;
        }

        $invoice->update([
            'sap_last_attempted_at' => now(),
        ]);

        if (! $invoice->sap_doc_entry) {
            $errorMessage = 'Invoice is missing SAP DocEntry required for cancellation.';

            $invoice->update([
                'sap_status' => 'posted',
                'sap_cancel_error_message' => $errorMessage,
                'sap_last_attempted_at' => now(),
            ]);

            DB::table('sap_logs')->insert([
                'invoice_id' => $invoice->id,
                'action' => 'cancel_invoice',
                'status' => 'failed',
                'request_payload' => json_encode(['doc_entry' => null]),
                'response_payload' => null,
                'error_message' => $errorMessage,
                'attempt_count' => $this->attempts(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $requestPayload = [
            'DocEntry' => (int) $invoice->sap_doc_entry,
            'Comments' => 'Cancelled from DDS Invoice #'.$invoice->id,
        ];

        try {
            $response = $sapService->cancelApInvoice(
                (string) $invoice->sap_doc_entry,
                $requestPayload['Comments']
            );

            DB::transaction(function () use ($invoice, $response, $requestPayload) {
                $invoice->update([
                    'sap_status' => 'cancelled',
                    'sap_cancelled_at' => now(),
                    'sap_cancellation_doc_num' => isset($response['DocNum']) ? (string) $response['DocNum'] : null,
                    'sap_cancellation_doc_entry' => isset($response['DocEntry']) ? (string) $response['DocEntry'] : null,
                    'sap_cancel_error_message' => null,
                    'sap_last_attempted_at' => now(),
                ]);

                DB::table('sap_logs')->insert([
                    'invoice_id' => $invoice->id,
                    'action' => 'cancel_invoice',
                    'status' => 'success',
                    'request_payload' => json_encode($requestPayload),
                    'response_payload' => json_encode($response),
                    'attempt_count' => $this->attempts(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            Log::channel('sap')->info('AP Invoice cancelled successfully', [
                'invoice_id' => $invoice->id,
                'sap_doc_entry' => $invoice->sap_doc_entry,
                'sap_cancellation_doc_num' => $response['DocNum'] ?? null,
            ]);
        } catch (\Exception $e) {
            $sapErrorMessage = $e instanceof RequestException
                ? $this->parseSapErrorMessage($e)
                : $e->getMessage();

            DB::table('sap_logs')->insert([
                'invoice_id' => $invoice->id,
                'action' => 'cancel_invoice',
                'status' => 'failed',
                'request_payload' => json_encode($requestPayload),
                'response_payload' => null,
                'error_message' => $sapErrorMessage,
                'attempt_count' => $this->attempts(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::channel('sap')->error('AP Invoice cancellation failed', [
                'invoice_id' => $invoice->id,
                'error' => $sapErrorMessage,
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1]);
            } else {
                $invoice->update([
                    'sap_status' => 'posted',
                    'sap_cancel_error_message' => $sapErrorMessage,
                    'sap_last_attempted_at' => now(),
                ]);

                Log::channel('sap')->error('Max cancel retries exceeded for invoice '.$invoice->id);
            }

            throw $e;
        }
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
