<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class InvoiceCreatorService
{
    /**
     * @param  array<string, mixed>  $data  Validated fields for a single invoice (not including `import_uuid`, `import_line_items`, `additional_document_ids`).
     * @param  array<int, array<string, mixed>>|null  $importLineItems
     * @param  array<int|string>  $additionalDocumentIds
     * @return array{invoice: Invoice, import_attachment_saved: bool}
     */
    public function create(
        array $data,
        int $userId,
        ?User $authUser = null,
        ?string $importUuid = null,
        ?array $importLineItems = null,
        array $additionalDocumentIds = []
    ): array {
        $receiveProject = $data['receive_project'] ?? null;
        if (! $receiveProject && $authUser && $authUser->project) {
            $receiveProject = $authUser->project;
        }

        $importExtraction = null;
        if ($importUuid !== null && $importUuid !== '') {
            $importExtraction = app(InvoiceImportAttachmentService::class)->getImportExtractionPayload(
                $importUuid,
                $userId
            );
        }

        $invoice = Invoice::create([
            'invoice_number' => $data['invoice_number'],
            'faktur_no' => $data['faktur_no'] ?? null,
            'invoice_date' => $data['invoice_date'],
            'receive_date' => $data['receive_date'],
            'supplier_id' => $data['supplier_id'],
            'po_no' => $data['po_no'] ?? null,
            'receive_project' => $receiveProject,
            'invoice_project' => $data['invoice_project'] ?? null,
            'payment_project' => $data['payment_project'] ?? null,
            'currency' => $data['currency'],
            'amount' => $data['amount'],
            'type_id' => $data['type_id'],
            'payment_date' => $data['payment_date'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'cur_loc' => $data['cur_loc'],
            'sap_doc' => $data['sap_doc'] ?? null,
            'status' => 'open',
            'created_by' => $userId,
            'import_extraction' => $importExtraction,
        ]);

        if ($additionalDocumentIds !== []) {
            $invoice->additionalDocuments()->sync(array_unique($additionalDocumentIds));
        }

        $importAttachmentSaved = false;
        if ($importUuid !== null && $importUuid !== '') {
            $importAttachmentSaved = app(InvoiceImportAttachmentService::class)->attachFromImport(
                $invoice,
                $importUuid,
                $userId
            );
            if (! $importAttachmentSaved) {
                Log::warning('Invoice import file could not be attached after create', [
                    'invoice_id' => $invoice->id,
                    'import_uuid' => $importUuid,
                ]);
            }
        }

        if ($importUuid !== null && $importUuid !== '' && is_array($importLineItems) && count($importLineItems) > 0) {
            app(InvoiceImportLineDetailsPersister::class)->persistFromUserInput($invoice, $importLineItems);
        } else {
            app(InvoiceImportLineDetailsPersister::class)->persistFromImportExtraction($invoice);
        }

        return [
            'invoice' => $invoice,
            'import_attachment_saved' => $importAttachmentSaved,
        ];
    }
}
