<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLineDetail;

class InvoiceImportLineDetailsPersister
{
    /**
     * Persist line items from `invoices.import_extraction` draft (PDF/image import). Informational only vs header amount.
     *
     * @return int Number of rows inserted
     */
    public function persistFromImportExtraction(Invoice $invoice): int
    {
        $extraction = $invoice->import_extraction;
        if (! is_array($extraction)) {
            return 0;
        }
        $draft = $extraction['draft'] ?? null;
        if (! is_array($draft)) {
            return 0;
        }
        $lines = $draft['line_items'] ?? [];
        if (! is_array($lines) || $lines === []) {
            return 0;
        }

        $invoice->lineDetails()->delete();

        $inserted = 0;
        $lineNo = 0;

        foreach ($lines as $row) {
            if (! is_array($row)) {
                continue;
            }
            $description = trim((string) ($row['description'] ?? ''));
            $quantity = $this->nullableNumeric($row['quantity'] ?? $row['qty'] ?? null);
            $unitPrice = $this->nullableNumeric($row['unit_price'] ?? null);
            $amount = $this->nullableNumeric($row['amount'] ?? null);

            if ($amount === null && $quantity !== null && $unitPrice !== null) {
                $amount = round($quantity * $unitPrice, 2);
            }

            if ($description === '' && $quantity === null && $unitPrice === null && $amount === null) {
                continue;
            }
            if ($description === '') {
                $description = '(no description)';
            }

            $lineNo++;
            InvoiceLineDetail::create([
                'invoice_id' => $invoice->id,
                'line_no' => $lineNo,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'source' => 'import',
            ]);
            $inserted++;
        }

        return $inserted;
    }

    private function nullableNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }
}
