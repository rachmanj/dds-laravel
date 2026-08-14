<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLineDetail;
use App\Models\SapDepartment;
use App\Models\SapProject;

class SapApInvoicePayloadBuilder
{
    protected Invoice $invoice;

    protected array $config;

    /**
     * @var array<int, array{grpo_no: string, doc_entry: int|string, base_line: int|string, item_code: string, quantity: float|int|string, unit_price?: float|int|string|null}>
     */
    protected array $grpoReferences;

    /**
     * @param  array<int, array{grpo_no: string, doc_entry: int|string, base_line: int|string, item_code: string, quantity: float|int|string, unit_price?: float|int|string|null}>  $grpoReferences
     */
    public function __construct(Invoice $invoice, array $grpoReferences = [])
    {
        $this->invoice = $invoice->loadMissing(['type', 'lineDetails', 'supplier', 'sapSubmitter']);
        $this->grpoReferences = $grpoReferences;
        $this->config = config('services.sap.ap_invoice', []);
    }

    /**
     * Build complete AP Invoice payload for SAP B1
     */
    public function build(): array
    {
        $this->validate();

        $payload = array_merge([
            'CardCode' => $this->mapSupplier(),
            'DocDueDate' => $this->mapDueDate(),
            'DocCurrency' => $this->invoice->currency,
            'NumAtCard' => $this->invoice->invoice_number,
            'Comments' => $this->invoice->remarks ?? 'Imported from DDS - Invoice #'.$this->invoice->id,
            'DocumentLines' => $this->mapLineItems(),
        ], $this->buildPostingAndFakturFields());

        if ($this->invoice->po_no) {
            $payload['Reference1'] = $this->invoice->po_no;
        }

        return $payload;
    }

    /**
     * Faktur UDF fields for post-create PATCH (DocDate cannot be updated after posting).
     *
     * @return array<string, string>
     */
    public function buildFakturPatchFields(): array
    {
        return $this->buildFakturUdfFields();
    }

    /**
     * @return array<string, string>
     */
    protected function buildPostingAndFakturFields(): array
    {
        return array_merge(
            [
                'DocDate' => $this->invoice->receive_date->format('Y-m-d'),
                'TaxDate' => $this->invoice->invoice_date->format('Y-m-d'),
            ],
            $this->buildFakturUdfFields()
        );
    }

    /**
     * @return array<string, string>
     */
    protected function buildFakturUdfFields(): array
    {
        $fields = [
            'U_MIS_FPDate' => $this->invoice->invoice_date->format('Y-m-d'),
        ];

        if ($this->invoice->faktur_no) {
            $fields['U_MIS_FPNum'] = $this->invoice->faktur_no;
        }

        $submittedByName = $this->mapSubmittedByName();
        if ($submittedByName !== null) {
            $fields['U_MIS_Created'] = $submittedByName;
        }

        return $fields;
    }

    /**
     * Map SAP submitter name to SAP U_MIS_Created
     */
    protected function mapSubmittedByName(): ?string
    {
        $name = trim((string) ($this->invoice->sapSubmitter?->name ?? ''));

        return $name !== '' ? $name : null;
    }

    /**
     * Validate invoice data before building payload
     */
    public function validate(): array
    {
        $errors = [];

        if (! $this->invoice->supplier || ! $this->invoice->supplier->sap_code) {
            $errors[] = 'Supplier does not have SAP code';
        }

        if ($this->invoice->amount <= 0) {
            $errors[] = 'Invoice amount must be greater than 0';
        }

        if (! $this->invoice->invoice_date) {
            $errors[] = 'Invoice date is required';
        }

        if (! $this->invoice->receive_date) {
            $errors[] = 'Receive date is required';
        }

        if ($this->invoice->isConsignment()) {
            if (! filled($this->invoice->gl_account)) {
                $errors[] = 'G/L account is required for consignment invoices';
            }

            if (empty($this->grpoReferences) && $this->invoice->lineDetails->isEmpty()) {
                $errors[] = 'Consignment invoices require at least one line item';
            }
        }

        if (! empty($errors)) {
            throw new \Exception('Validation failed: '.implode(', ', $errors));
        }

        return [];
    }

    /**
     * Map supplier to SAP CardCode
     */
    protected function mapSupplier(): string
    {
        if (! $this->invoice->supplier || ! $this->invoice->supplier->sap_code) {
            throw new \Exception('Supplier does not have SAP code');
        }

        return $this->invoice->supplier->sap_code;
    }

    /**
     * Map payment due date
     */
    protected function mapDueDate(): string
    {
        if ($this->invoice->payment_date) {
            return $this->invoice->payment_date->format('Y-m-d');
        }

        $paymentTerms = $this->config['default_payment_terms'] ?? 30;

        return $this->invoice->invoice_date->copy()->addDays($paymentTerms)->format('Y-m-d');
    }

    /**
     * Map line items — GRPO-based draw-from-base or standalone service line
     */
    protected function mapLineItems(): array
    {
        $projectCode = $this->mapProjectCode();
        $costingCode = $this->mapCostingCode();
        $taxCode = $this->determineTaxCode();
        $defaultItemCode = $this->config['default_item_code'] ?? 'SERVICE';
        $consignmentAccountCode = $this->invoice->isConsignment() && filled($this->invoice->gl_account)
            ? $this->invoice->gl_account
            : null;

        if (! empty($this->grpoReferences)) {
            return array_map(function (array $ref) use ($projectCode, $costingCode, $taxCode, $defaultItemCode, $consignmentAccountCode) {
                $line = [
                    'ItemCode' => $ref['item_code'] ?: $defaultItemCode,
                    'Quantity' => (float) $ref['quantity'],
                    'TaxCode' => $taxCode,
                    'ProjectCode' => $projectCode,
                    'CostingCode' => $costingCode,
                    'BaseType' => 20,
                    'BaseEntry' => (int) $ref['doc_entry'],
                    'BaseLine' => (int) $ref['base_line'],
                ];

                if (isset($ref['unit_price']) && $ref['unit_price'] !== null && $ref['unit_price'] !== '') {
                    $line['UnitPrice'] = (float) $ref['unit_price'];
                }

                if ($consignmentAccountCode !== null) {
                    $line['AccountCode'] = $consignmentAccountCode;
                }

                return $line;
            }, $this->grpoReferences);
        }

        if ($this->invoice->isConsignment()) {
            return $this->mapConsignmentLineItems($projectCode, $costingCode, $taxCode);
        }

        return [
            [
                'ItemCode' => $defaultItemCode,
                'Quantity' => 1,
                'UnitPrice' => $this->invoice->amount,
                'TaxCode' => $taxCode,
                'LineTotal' => $this->invoice->amount,
                'ProjectCode' => $projectCode,
                'CostingCode' => $costingCode,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function mapConsignmentLineItems(?string $projectCode, ?string $costingCode, string $taxCode): array
    {
        $itemCode = $this->config['consignment_item_code'] ?? 'CONSIGNMENT';
        $accountCode = $this->invoice->gl_account;

        return $this->invoice->lineDetails->map(function (InvoiceLineDetail $detail) use ($itemCode, $projectCode, $costingCode, $taxCode, $accountCode) {
            $quantity = (float) ($detail->quantity ?? 1);
            if ($quantity <= 0) {
                $quantity = 1;
            }

            $unitPrice = $detail->unit_price !== null
                ? (float) $detail->unit_price
                : (float) ($detail->amount ?? 0);
            $lineTotal = $detail->amount !== null
                ? (float) $detail->amount
                : round($quantity * $unitPrice, 2);

            $line = [
                'ItemCode' => $itemCode,
                'Quantity' => $quantity,
                'UnitPrice' => $unitPrice,
                'LineTotal' => $lineTotal,
                'TaxCode' => $taxCode,
                'ProjectCode' => $projectCode,
                'CostingCode' => $costingCode,
            ];

            if (filled($accountCode)) {
                $line['AccountCode'] = $accountCode;
            }

            return $line;
        })->values()->all();
    }

    /**
     * Map project code from invoice to SAP ProjectCode
     */
    protected function mapProjectCode(): ?string
    {
        if (! $this->invoice->invoice_project) {
            return null;
        }

        $sapProject = SapProject::where('sap_code', $this->invoice->invoice_project)
            ->active()
            ->first();

        if ($sapProject) {
            return $sapProject->sap_code;
        }

        $sapProject = SapProject::where('name', $this->invoice->invoice_project)
            ->active()
            ->first();

        return $sapProject?->sap_code;
    }

    /**
     * Map cost center from invoice location to SAP CostingCode
     */
    protected function mapCostingCode(): ?string
    {
        if (! $this->invoice->cur_loc) {
            return null;
        }

        $sapDepartment = SapDepartment::where('sap_code', $this->invoice->cur_loc)
            ->active()
            ->first();

        if ($sapDepartment) {
            return $sapDepartment->sap_code;
        }

        $sapDepartment = SapDepartment::where('name', $this->invoice->cur_loc)
            ->active()
            ->first();

        if ($sapDepartment) {
            return $sapDepartment->sap_code;
        }

        return $this->config['default_costing_code'] ?? null;
    }

    /**
     * Determine tax code for invoice
     */
    protected function determineTaxCode(): string
    {
        $taxConfig = $this->config['tax_codes'] ?? [];

        if ($this->invoice->isConsignment()) {
            return $taxConfig['by_invoice_type']['Consignment'] ?? 'B111';
        }

        if (isset($taxConfig['by_currency'][$this->invoice->currency])) {
            return $taxConfig['by_currency'][$this->invoice->currency];
        }

        if ($this->invoice->type && isset($taxConfig['by_invoice_type'][$this->invoice->type->type_name])) {
            return $taxConfig['by_invoice_type'][$this->invoice->type->type_name];
        }

        return $taxConfig['default'] ?? 'EXEMPT';
    }

    /**
     * Get preview data for UI
     */
    public function getPreviewData(): array
    {
        $projectCode = $this->mapProjectCode();
        $costingCode = $this->mapCostingCode();

        return [
            'ap_invoice' => [
                'supplier' => [
                    'code' => $this->invoice->supplier->sap_code ?? null,
                    'name' => $this->invoice->supplier->name ?? null,
                ],
                'invoice_number' => $this->invoice->invoice_number,
                'invoice_date' => $this->invoice->invoice_date->format('Y-m-d'),
                'posting_date' => $this->invoice->receive_date->format('Y-m-d'),
                'document_date' => $this->invoice->invoice_date->format('Y-m-d'),
                'faktur_no' => $this->invoice->faktur_no,
                'faktur_date' => $this->invoice->invoice_date->format('Y-m-d'),
                'submitted_by_name' => $this->mapSubmittedByName(),
                'due_date' => $this->mapDueDate(),
                'amount' => $this->invoice->amount,
                'currency' => $this->invoice->currency,
                'po_no' => $this->invoice->po_no,
                'project' => [
                    'code' => $projectCode,
                    'name' => $projectCode ? SapProject::where('sap_code', $projectCode)->first()?->name : null,
                ],
                'cost_center' => [
                    'code' => $costingCode,
                    'name' => $costingCode ? SapDepartment::where('sap_code', $costingCode)->first()?->name : null,
                ],
                'tax_code' => $this->determineTaxCode(),
                'gl_account' => $this->invoice->gl_account,
                'is_consignment' => $this->invoice->isConsignment(),
                'document_lines' => $this->mapLineItems(),
                'grpo_linked' => ! empty($this->grpoReferences),
                'standalone' => empty($this->grpoReferences) && empty($this->invoice->po_no),
            ],
        ];
    }
}
