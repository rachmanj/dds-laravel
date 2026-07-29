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

    protected Invoice $invoice;

    /**
     * @var array<int, array{grpo_no: string, doc_entry: int|string, base_line: int|string, item_code: string, quantity: float|int|string, unit_price?: float|int|string|null}>
     */
    protected array $grpoReferences;

    /**
     * @param  array<int, array{grpo_no: string, doc_entry: int|string, base_line: int|string, item_code: string, quantity: float|int|string, unit_price?: float|int|string|null}>  $grpoReferences
     */
    public function __construct(Invoice $invoice, array $grpoReferences = [])
    {
        $this->invoice = $invoice;
        $this->grpoReferences = $grpoReferences;
    }

    public function handle(SapService $sapService)
    {
        $invoice = $this->invoice->fresh(['supplier', 'type']);
        if (! $invoice) {
            Log::channel('sap')->error('CreateSapApInvoiceJob: Invoice reference could not be reloaded.');

            return;
        }

        if ($invoice->sap_status === 'posted' && $invoice->sap_doc_num) {
            Log::channel('sap')->info('CreateSapApInvoiceJob: Invoice already posted to SAP, skipping.', [
                'invoice_id' => $invoice->id,
                'sap_doc_num' => $invoice->sap_doc_num,
            ]);

            return;
        }

        $invoice->update([
            'sap_last_attempted_at' => now(),
        ]);

        $payload = null;

        try {
            // Validate supplier has SAP code
            if (! $invoice->supplier || ! $invoice->supplier->sap_code) {
                throw new \Exception('Supplier '.($invoice->supplier->name ?? ('#'.$invoice->supplier_id)).' does not have a SAP CardCode mapping.');
            }

            // Validate supplier exists in SAP
            $vendor = $this->resolveVendor($sapService, $invoice->supplier->sap_code);

            $payloadBuilder = new SapApInvoicePayloadBuilder($invoice, $this->grpoReferences);
            $payload = $payloadBuilder->build();

            // Create AP Invoice in SAP
            $response = $sapService->createApInvoice($payload);

            $fakturPatch = $payloadBuilder->buildFakturPatchFields();
            if (! empty($fakturPatch) && isset($response['DocEntry'])) {
                try {
                    $sapService->updateApInvoice($response['DocEntry'], $fakturPatch);
                    Log::channel('sap')->info('AP Invoice faktur UDF fields patched', [
                        'invoice_id' => $invoice->id,
                        'doc_entry' => $response['DocEntry'],
                        'fields' => array_keys($fakturPatch),
                    ]);
                } catch (\Throwable $patchException) {
                    Log::channel('sap')->warning('AP Invoice faktur UDF patch failed', [
                        'invoice_id' => $invoice->id,
                        'doc_entry' => $response['DocEntry'],
                        'error' => $patchException->getMessage(),
                    ]);
                }
            }

            DB::transaction(function () use ($invoice, $response, $payload) {
                $docNum = isset($response['DocNum']) ? (string) $response['DocNum'] : null;

                $this->persistPostedInvoice($invoice, [
                    'sap_status' => 'posted',
                    'sap_doc_num' => $docNum,
                    'sap_doc_entry' => isset($response['DocEntry']) ? (string) $response['DocEntry'] : null,
                    'sap_grpo_references' => ! empty($this->grpoReferences) ? $this->grpoReferences : null,
                    'sap_error_message' => null,
                    'sap_last_attempted_at' => now(),
                ], $docNum, $payload, $response);
            });

            Log::channel('sap')->info('AP Invoice created successfully', [
                'invoice_id' => $invoice->id,
                'sap_doc_num' => $response['DocNum'] ?? null,
            ]);
        } catch (\Exception $e) {
            $sapErrorMessage = $e instanceof RequestException
                ? $this->parseSapErrorMessage($e)
                : $e->getMessage();

            if ($invoice->sap_status === 'posted' && $invoice->sap_doc_num) {
                Log::channel('sap')->warning('CreateSapApInvoiceJob: Duplicate post attempt failed but invoice is already posted.', [
                    'invoice_id' => $invoice->id,
                    'sap_doc_num' => $invoice->sap_doc_num,
                    'error' => $sapErrorMessage,
                ]);

                return;
            }

            $existingSapInvoice = $this->findExistingSapInvoice($sapService, $invoice);
            if ($existingSapInvoice) {
                $this->markInvoicePostedFromSap($invoice, $existingSapInvoice, $payload);

                Log::channel('sap')->warning('CreateSapApInvoiceJob: Reconciled invoice from existing SAP document after post failure.', [
                    'invoice_id' => $invoice->id,
                    'sap_doc_num' => $existingSapInvoice['DocNum'] ?? null,
                    'error' => $sapErrorMessage,
                ]);

                return;
            }

            $invoice->update([
                'sap_status' => 'failed',
                'sap_error_message' => $sapErrorMessage,
                'sap_last_attempted_at' => now(),
            ]);

            DB::table('sap_logs')->insert([
                'invoice_id' => $invoice->id,
                'action' => 'create_invoice',
                'status' => 'failed',
                'request_payload' => json_encode($payload ?? []),
                'response_payload' => null,
                'error_message' => $sapErrorMessage,
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

    /**
     * @return array{DocEntry?: int, DocNum?: int, NumAtCard?: string}|null
     */
    protected function findExistingSapInvoice(SapService $sapService, Invoice $invoice): ?array
    {
        if (! $invoice->invoice_number) {
            return null;
        }

        $filterValue = str_replace("'", "''", $invoice->invoice_number);

        try {
            $result = $sapService->get('PurchaseInvoices', [
                'query' => [
                    '$filter' => "NumAtCard eq '{$filterValue}' and Cancelled eq 'N'",
                    '$select' => 'DocEntry,DocNum,NumAtCard,Cancelled',
                    '$top' => 1,
                ],
            ]);

            $rows = $result['value'] ?? [];
            if (empty($rows)) {
                return null;
            }

            $row = $rows[0];
            if (strtoupper((string) ($row['Cancelled'] ?? 'N')) === 'Y') {
                return null;
            }

            return $row;
        } catch (\Throwable $exception) {
            Log::channel('sap')->warning('CreateSapApInvoiceJob: Unable to search existing SAP invoice.', [
                'invoice_id' => $invoice->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array{DocEntry?: int, DocNum?: int}  $existingSapInvoice
     */
    protected function markInvoicePostedFromSap(Invoice $invoice, array $existingSapInvoice, ?array $payload): void
    {
        $docNum = isset($existingSapInvoice['DocNum']) ? (string) $existingSapInvoice['DocNum'] : null;

        DB::transaction(function () use ($invoice, $existingSapInvoice, $payload, $docNum) {
            $this->persistPostedInvoice($invoice, [
                'sap_status' => 'posted',
                'sap_doc_num' => $docNum,
                'sap_doc_entry' => isset($existingSapInvoice['DocEntry']) ? (string) $existingSapInvoice['DocEntry'] : null,
                'sap_grpo_references' => ! empty($this->grpoReferences) ? $this->grpoReferences : $invoice->sap_grpo_references,
                'sap_error_message' => null,
                'sap_last_attempted_at' => now(),
            ], $docNum, $payload, $existingSapInvoice);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function persistPostedInvoice(
        Invoice $invoice,
        array $attributes,
        ?string $docNum,
        ?array $payload,
        array|string|null $responsePayload
    ): void {
        $attributes['status'] = 'sap';

        if ($docNum && $this->canAssignSapDoc($docNum, $invoice->id)) {
            $attributes['sap_doc'] = $docNum;
        }

        $invoice->update($attributes);

        DB::table('sap_logs')->insert([
            'invoice_id' => $invoice->id,
            'action' => 'create_invoice',
            'status' => 'success',
            'request_payload' => json_encode($payload ?? []),
            'response_payload' => is_string($responsePayload) ? $responsePayload : json_encode($responsePayload),
            'attempt_count' => $this->attempts(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function canAssignSapDoc(string $docNum, int $invoiceId): bool
    {
        return ! Invoice::query()
            ->where('sap_doc', $docNum)
            ->where('id', '!=', $invoiceId)
            ->exists();
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
