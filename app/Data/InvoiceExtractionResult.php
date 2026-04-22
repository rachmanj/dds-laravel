<?php

namespace App\Data;

final class InvoiceExtractionResult
{
    /**
     * @param  array<int, array{description: string, quantity: float|null, unit_price: float|null, amount: float|null}>  $lineItems
     * @param  array<int, string>  $warnings
     * @param  array<int, string>  $lowConfidenceFields
     */
    public function __construct(
        public readonly ?string $invoiceNumber,
        public readonly ?string $fakturNo,
        public readonly ?string $invoiceDate,
        public readonly ?string $receiveDate,
        public readonly ?string $supplierNameRaw,
        public readonly ?string $supplierTaxId,
        public readonly ?string $poNo,
        public readonly ?string $currency,
        public readonly ?float $amount,
        public readonly array $lineItems,
        public readonly float $confidence,
        public readonly array $warnings,
        public readonly array $lowConfidenceFields,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromOpenRouterArray(array $data): self
    {
        $lineItems = [];
        foreach ($data['line_items'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $qtyRaw = $row['quantity'] ?? $row['qty'] ?? null;
            $lineItems[] = [
                'description' => isset($row['description']) ? (string) $row['description'] : '',
                'quantity' => ($qtyRaw !== null && $qtyRaw !== '' && is_numeric($qtyRaw)) ? (float) $qtyRaw : null,
                'unit_price' => isset($row['unit_price']) && is_numeric($row['unit_price']) ? (float) $row['unit_price'] : null,
                'amount' => isset($row['amount']) && is_numeric($row['amount']) ? (float) $row['amount'] : null,
            ];
        }

        $amount = null;
        if (isset($data['amount']) && is_numeric($data['amount'])) {
            $amount = (float) $data['amount'];
        }

        $confidence = 0.5;
        if (isset($data['confidence']) && is_numeric($data['confidence'])) {
            $confidence = min(1.0, max(0.0, (float) $data['confidence']));
        }

        $warnings = [];
        foreach ($data['warnings'] ?? [] as $w) {
            if (is_string($w) && $w !== '') {
                $warnings[] = $w;
            }
        }

        $low = [];
        foreach ($data['low_confidence_fields'] ?? [] as $f) {
            if (is_string($f) && $f !== '') {
                $low[] = $f;
            }
        }

        return new self(
            invoiceNumber: isset($data['invoice_number']) ? self::trimOrNull((string) $data['invoice_number']) : null,
            fakturNo: isset($data['faktur_no']) ? self::trimOrNull((string) $data['faktur_no']) : null,
            invoiceDate: isset($data['invoice_date']) ? self::trimOrNull((string) $data['invoice_date']) : null,
            receiveDate: isset($data['receive_date']) ? self::trimOrNull((string) $data['receive_date']) : null,
            supplierNameRaw: isset($data['supplier_name']) ? self::trimOrNull((string) $data['supplier_name']) : null,
            supplierTaxId: isset($data['supplier_tax_id']) ? self::trimOrNull((string) $data['supplier_tax_id']) : null,
            poNo: isset($data['po_no']) ? self::trimOrNull((string) $data['po_no']) : null,
            currency: isset($data['currency']) ? self::normalizeCurrency((string) $data['currency']) : null,
            amount: $amount,
            lineItems: $lineItems,
            confidence: $confidence,
            warnings: $warnings,
            lowConfidenceFields: $low,
        );
    }

    private static function trimOrNull(string $v): ?string
    {
        $v = trim($v);

        return $v === '' ? null : $v;
    }

    private static function normalizeCurrency(string $c): ?string
    {
        $c = strtoupper(trim($c));
        if (strlen($c) === 3 && ctype_alpha($c)) {
            return $c;
        }

        return null;
    }
}
