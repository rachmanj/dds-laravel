<?php

namespace App\Services;

use App\Models\Invoice;

class SapOutgoingPaymentPayloadBuilder
{
    protected Invoice $invoice;

    protected string $paymentMeans;

    protected string $accountCode;

    protected int $apInvoiceDocEntry;

    protected ?string $paymentDate;

    public function __construct(
        Invoice $invoice,
        string $paymentMeans,
        string $accountCode,
        int $apInvoiceDocEntry,
        ?string $paymentDate = null
    ) {
        $this->invoice = $invoice;
        $this->paymentMeans = $paymentMeans;
        $this->accountCode = trim($accountCode);
        $this->apInvoiceDocEntry = $apInvoiceDocEntry;
        $this->paymentDate = $paymentDate;
    }

    public function build(): array
    {
        $this->validate();

        $docDate = $this->resolvePaymentDate();
        $amount = (float) $this->invoice->amount;

        $payload = [
            'DocType' => 'rSupplier',
            'CardCode' => $this->mapSupplier(),
            'DocDate' => $docDate,
            'DocCurrency' => $this->invoice->currency,
            'PaymentInvoices' => [
                [
                    'DocEntry' => $this->apInvoiceDocEntry,
                    'SumApplied' => $amount,
                    'InvoiceType' => 'it_PurchaseInvoice',
                    'InstallmentId' => 1,
                ],
            ],
        ];

        if ($this->paymentMeans === 'cash') {
            $payload['CashAccount'] = $this->accountCode;
            $payload['CashSum'] = $amount;
        } else {
            $payload['TransferAccount'] = $this->accountCode;
            $payload['TransferSum'] = $amount;
            $payload['TransferDate'] = $docDate;
            $payload['TransferReference'] = $this->invoice->invoice_number;
        }

        return $payload;
    }

    public function validate(): array
    {
        $errors = [];

        if ($this->invoice->payment_status !== 'paid') {
            $errors[] = 'Invoice must be marked as paid locally before submitting payment to SAP';
        }

        if ($this->invoice->sap_status !== 'posted' || ! $this->invoice->sap_doc_entry) {
            $errors[] = 'Invoice must be posted to SAP as an AP Invoice before submitting payment';
        }

        if ($this->invoice->sap_payment_status === 'posted') {
            $errors[] = 'Payment is already posted to SAP';
        }

        if (! $this->invoice->supplier || ! $this->invoice->supplier->sap_code) {
            $errors[] = 'Supplier does not have SAP code';
        }

        if ($this->invoice->amount <= 0) {
            $errors[] = 'Invoice amount must be greater than 0';
        }

        if ($this->accountCode === '') {
            $errors[] = 'Payment account code is required';
        }

        if ($this->apInvoiceDocEntry <= 0) {
            $errors[] = 'Valid SAP AP Invoice DocEntry is required';
        }

        if (! in_array($this->paymentMeans, ['cash', 'transfer'], true)) {
            $errors[] = 'Payment means must be cash or transfer';
        }

        if (! empty($errors)) {
            throw new \Exception('Validation failed: '.implode(', ', $errors));
        }

        return [];
    }

    public function getPreviewData(): array
    {
        $docDate = $this->resolvePaymentDate();
        $amount = (float) $this->invoice->amount;

        return [
            'outgoing_payment' => [
                'supplier' => [
                    'code' => $this->invoice->supplier->sap_code ?? null,
                    'name' => $this->invoice->supplier->name ?? null,
                ],
                'invoice_number' => $this->invoice->invoice_number,
                'payment_date' => $docDate,
                'amount' => $amount,
                'currency' => $this->invoice->currency,
                'payment_means' => $this->paymentMeans,
                'account_code' => $this->accountCode,
                'ap_invoice' => [
                    'doc_entry' => $this->apInvoiceDocEntry,
                    'doc_num' => $this->invoice->sap_doc_num,
                ],
                'payment_invoices' => [
                    [
                        'DocEntry' => $this->apInvoiceDocEntry,
                        'SumApplied' => $amount,
                        'InvoiceType' => 'it_PurchaseInvoice',
                        'InstallmentId' => 1,
                    ],
                ],
            ],
        ];
    }

    protected function mapSupplier(): string
    {
        if (! $this->invoice->supplier || ! $this->invoice->supplier->sap_code) {
            throw new \Exception('Supplier does not have SAP code');
        }

        return $this->invoice->supplier->sap_code;
    }

    protected function resolvePaymentDate(): string
    {
        if ($this->paymentDate) {
            return $this->paymentDate;
        }

        if ($this->invoice->payment_date) {
            return $this->invoice->payment_date->format('Y-m-d');
        }

        return now()->format('Y-m-d');
    }
}
