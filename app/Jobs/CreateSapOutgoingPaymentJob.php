<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\SapApInvoicePaymentResolver;
use App\Services\SapOutgoingPaymentPayloadBuilder;
use App\Services\SapService;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateSapOutgoingPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300, 900];

    protected Invoice $invoice;

    protected string $paymentMeans;

    protected string $accountCode;

    protected ?string $paymentDate;

    public function __construct(Invoice $invoice, string $paymentMeans, string $accountCode, ?string $paymentDate = null)
    {
        $this->invoice = $invoice;
        $this->paymentMeans = $paymentMeans;
        $this->accountCode = $accountCode;
        $this->paymentDate = $paymentDate;
    }

    public function handle(SapService $sapService, SapApInvoicePaymentResolver $apInvoiceResolver): void
    {
        $invoice = $this->invoice->fresh(['supplier']);
        if (! $invoice) {
            Log::channel('sap')->error('CreateSapOutgoingPaymentJob: Invoice reference could not be reloaded.');

            return;
        }

        if ($invoice->sap_payment_status === 'posted' && $invoice->sap_payment_doc_num) {
            Log::channel('sap')->info('CreateSapOutgoingPaymentJob: Payment already posted to SAP, skipping.', [
                'invoice_id' => $invoice->id,
                'sap_payment_doc_num' => $invoice->sap_payment_doc_num,
            ]);

            return;
        }

        $invoice->update([
            'sap_payment_last_attempted_at' => now(),
        ]);

        $payload = null;

        try {
            $validationErrors = $invoice->canProcessPaymentToSapJob();
            if (! empty($validationErrors)) {
                throw new \Exception(implode(', ', $validationErrors));
            }

            $resolvedApInvoice = $apInvoiceResolver->resolve($invoice, $sapService);
            $this->syncResolvedApInvoice($invoice, $resolvedApInvoice);

            $payloadBuilder = new SapOutgoingPaymentPayloadBuilder(
                $invoice,
                $this->paymentMeans,
                $this->accountCode,
                (int) $resolvedApInvoice['DocEntry'],
                $this->paymentDate
            );
            $payload = $payloadBuilder->build();

            $response = $sapService->createOutgoingPayment($payload);

            DB::transaction(function () use ($invoice, $response, $payload) {
                $docNum = isset($response['DocNum']) ? (string) $response['DocNum'] : null;

                $invoice->update([
                    'sap_payment_status' => 'posted',
                    'sap_payment_doc_num' => $docNum,
                    'sap_payment_doc_entry' => isset($response['DocEntry']) ? (string) $response['DocEntry'] : null,
                    'sap_payment_means' => $this->paymentMeans,
                    'sap_payment_account_code' => $this->accountCode,
                    'sap_payment_error_message' => null,
                    'sap_payment_last_attempted_at' => now(),
                ]);

                DB::table('sap_logs')->insert([
                    'invoice_id' => $invoice->id,
                    'action' => 'create_payment',
                    'status' => 'success',
                    'request_payload' => json_encode($payload),
                    'response_payload' => json_encode($response),
                    'attempt_count' => $this->attempts(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            Log::channel('sap')->info('Outgoing Payment created successfully', [
                'invoice_id' => $invoice->id,
                'sap_payment_doc_num' => $response['DocNum'] ?? null,
            ]);
        } catch (\Exception $e) {
            $sapErrorMessage = $e instanceof RequestException
                ? $this->parseSapErrorMessage($e)
                : $e->getMessage();

            if ($invoice->sap_payment_status === 'posted' && $invoice->sap_payment_doc_num) {
                Log::channel('sap')->warning('CreateSapOutgoingPaymentJob: Duplicate post attempt failed but payment is already posted.', [
                    'invoice_id' => $invoice->id,
                    'sap_payment_doc_num' => $invoice->sap_payment_doc_num,
                    'error' => $sapErrorMessage,
                ]);

                return;
            }

            $invoice->update([
                'sap_payment_status' => 'failed',
                'sap_payment_error_message' => $sapErrorMessage,
                'sap_payment_last_attempted_at' => now(),
            ]);

            DB::table('sap_logs')->insert([
                'invoice_id' => $invoice->id,
                'action' => 'create_payment',
                'status' => 'failed',
                'request_payload' => json_encode($payload ?? []),
                'response_payload' => null,
                'error_message' => $sapErrorMessage,
                'attempt_count' => $this->attempts(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::channel('sap')->error('Outgoing Payment creation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1]);
            } else {
                Log::channel('sap')->error('Max retries exceeded for outgoing payment on invoice '.$invoice->id);
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

    /**
     * @param  array{DocEntry: int, DocNum?: int|string}  $resolvedApInvoice
     */
    protected function syncResolvedApInvoice(Invoice $invoice, array $resolvedApInvoice): void
    {
        $docEntry = isset($resolvedApInvoice['DocEntry']) ? (string) $resolvedApInvoice['DocEntry'] : null;
        $docNum = isset($resolvedApInvoice['DocNum']) ? (string) $resolvedApInvoice['DocNum'] : null;

        if ($docEntry === $invoice->sap_doc_entry && $docNum === $invoice->sap_doc_num) {
            return;
        }

        $invoice->update([
            'sap_doc_entry' => $docEntry,
            'sap_doc_num' => $docNum,
        ]);
    }
}
