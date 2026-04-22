<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceLineDetail;
use App\Models\Supplier;

class PertaminaSolarInvoiceResolver
{
    public const PERTAMINA_SUPPLIER_NAME = 'PERTAMINA';

    public function resolveLast(): ?array
    {
        $supplier = Supplier::query()
            ->where('name', self::PERTAMINA_SUPPLIER_NAME)
            ->first();

        if (! $supplier) {
            return null;
        }

        $invoice = Invoice::query()
            ->where('supplier_id', $supplier->id)
            ->whereHas('lineDetails', function ($q): void {
                $q->where('description', 'like', '%SOLAR%');
            })
            ->with(['supplier', 'lineDetails' => function ($q): void {
                $q->where('description', 'like', '%SOLAR%')
                    ->orderBy('line_no');
            }])
            ->orderByDesc('id')
            ->first();

        if (! $invoice || $invoice->lineDetails->isEmpty()) {
            return null;
        }

        $line = $invoice->lineDetails->first();
        $unitPrice = $this->resolveUnitPrice($line);

        return [
            'invoice' => $invoice,
            'line' => $line,
            'unit_price' => $unitPrice,
        ];
    }

    private function resolveUnitPrice(InvoiceLineDetail $line): ?string
    {
        if ($line->unit_price !== null && (float) (string) $line->unit_price != 0.0) {
            return (string) $line->unit_price;
        }

        if ($line->amount === null || $line->quantity === null) {
            return null;
        }

        if ((float) (string) $line->quantity == 0.0) {
            return null;
        }

        return bcdiv((string) $line->amount, (string) $line->quantity, 4);
    }
}
