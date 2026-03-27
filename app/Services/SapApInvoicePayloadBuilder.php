<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\SapDepartment;
use App\Models\SapProject;

class SapApInvoicePayloadBuilder
{
    protected Invoice $invoice;

    protected array $config;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        $this->config = config('services.sap.ap_invoice', []);
    }

    /**
     * Build complete AP Invoice payload for SAP B1
     */
    public function build(): array
    {
        $this->validate();

        $payload = [
            'CardCode' => $this->mapSupplier(),
            'DocDate' => $this->invoice->invoice_date->format('Y-m-d'),
            'DocDueDate' => $this->mapDueDate(),
            'DocCurrency' => $this->invoice->currency,
            'NumAtCard' => $this->invoice->invoice_number,
            'Comments' => $this->invoice->remarks ?? 'Imported from DDS - Invoice #'.$this->invoice->id,
            'DocumentLines' => $this->mapLineItems(),
        ];

        // Add PO reference if available
        if ($this->invoice->po_no) {
            $payload['Reference1'] = $this->invoice->po_no;
        }

        return $payload;
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

        // Default: payment terms days from invoice date
        $paymentTerms = $this->config['default_payment_terms'] ?? 30;

        return $this->invoice->invoice_date->copy()->addDays($paymentTerms)->format('Y-m-d');
    }

    /**
     * Map line items
     */
    protected function mapLineItems(): array
    {
        $projectCode = $this->mapProjectCode();
        $costingCode = $this->mapCostingCode();
        $taxCode = $this->determineTaxCode();

        return [
            [
                'ItemCode' => $this->config['default_item_code'] ?? 'SERVICE',
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
     * Map project code from invoice to SAP ProjectCode
     */
    protected function mapProjectCode(): ?string
    {
        if (! $this->invoice->invoice_project) {
            return null;
        }

        // Try direct match by sap_code first
        $sapProject = SapProject::where('sap_code', $this->invoice->invoice_project)
            ->active()
            ->first();

        if ($sapProject) {
            return $sapProject->sap_code;
        }

        // Try match by name (if invoice_project contains name instead of code)
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

        // Try direct match by sap_code first
        $sapDepartment = SapDepartment::where('sap_code', $this->invoice->cur_loc)
            ->active()
            ->first();

        if ($sapDepartment) {
            return $sapDepartment->sap_code;
        }

        // Try match by name (if cur_loc contains name instead of code)
        $sapDepartment = SapDepartment::where('name', $this->invoice->cur_loc)
            ->active()
            ->first();

        return $sapDepartment?->sap_code;
    }

    /**
     * Determine tax code for invoice
     */
    protected function determineTaxCode(): string
    {
        $taxConfig = $this->config['tax_codes'] ?? [];

        // Check by currency
        if (isset($taxConfig['by_currency'][$this->invoice->currency])) {
            return $taxConfig['by_currency'][$this->invoice->currency];
        }

        // Check by invoice type (if configured)
        if ($this->invoice->type && isset($taxConfig['by_invoice_type'][$this->invoice->type->type_name])) {
            return $taxConfig['by_invoice_type'][$this->invoice->type->type_name];
        }

        // Default
        return $taxConfig['default'] ?? 'EXEMPT';
    }

    /**
     * Get preview data for UI (future use)
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
            ],
        ];
    }
}
