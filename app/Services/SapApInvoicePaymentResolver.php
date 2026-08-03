<?php

namespace App\Services;

use App\Models\Invoice;

class SapApInvoicePaymentResolver
{
    /**
     * @return array{DocEntry: int, DocNum: int|string, CardCode: string, DocumentStatus?: string, Cancelled?: string, NumAtCard?: string}
     */
    public function resolve(Invoice $invoice, SapService $sapService): array
    {
        $invoice->loadMissing('supplier');

        $expectedCardCode = $invoice->supplier?->sap_code;
        if (! $expectedCardCode) {
            throw new \Exception('Supplier does not have SAP code');
        }

        $candidates = [];

        if ($invoice->sap_doc_entry) {
            $candidates[] = ['source' => 'sap_doc_entry', 'lookup' => fn () => $sapService->getPurchaseInvoiceByDocEntry((string) $invoice->sap_doc_entry)];
        }

        if ($invoice->sap_doc_num) {
            $candidates[] = ['source' => 'sap_doc_num', 'lookup' => fn () => $sapService->getPurchaseInvoiceByDocNum((string) $invoice->sap_doc_num)];
        }

        if ($invoice->invoice_number) {
            $candidates[] = ['source' => 'invoice_number', 'lookup' => fn () => $sapService->getPurchaseInvoiceByNumAtCard($invoice->invoice_number)];
        }

        foreach ($candidates as $candidate) {
            $document = $candidate['lookup']();
            if (! $document) {
                continue;
            }

            $this->assertPayableApInvoice($document, $expectedCardCode, $candidate['source']);

            return $document;
        }

        throw new \Exception(
            'Linked SAP AP Invoice could not be found. Stored DocEntry '
            .($invoice->sap_doc_entry ?: 'n/a')
            .', DocNum '.($invoice->sap_doc_num ?: 'n/a')
            .'. Re-post the AP Invoice to SAP or verify the document still exists in SAP B1.'
        );
    }

    /**
     * @param  array{DocEntry: int, DocNum?: int|string, CardCode?: string, DocumentStatus?: string, Cancelled?: string}  $document
     */
    protected function assertPayableApInvoice(array $document, string $expectedCardCode, string $source): void
    {
        $cardCode = $document['CardCode'] ?? null;
        if ($cardCode && $cardCode !== $expectedCardCode) {
            throw new \Exception(
                "SAP AP Invoice from {$source} belongs to vendor {$cardCode}, expected {$expectedCardCode}."
            );
        }

        if (strtoupper((string) ($document['Cancelled'] ?? 'N')) === 'Y') {
            throw new \Exception('Linked SAP AP Invoice is cancelled and cannot be paid.');
        }

        $status = $document['DocumentStatus'] ?? null;
        if ($status && $status !== 'bost_Open') {
            throw new \Exception("Linked SAP AP Invoice is not open for payment (status: {$status}).");
        }
    }
}
