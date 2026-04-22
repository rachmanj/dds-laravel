<?php

namespace App\Services;

use App\Data\InvoiceExtractionResult;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;

class InvoiceImportDraftBuilder
{
    public function __construct(
        private InvoiceImportSupplierResolver $supplierResolver
    ) {}

    /**
     * @param  array{supplier_id: int|null, candidates: array<int, array{id: int, name: string, score: float}>}  $supplierMatch
     * @return array<string, mixed>
     */
    public function build(User $user, InvoiceExtractionResult $extraction, array $supplierMatch): array
    {
        $today = Carbon::now()->toDateString();
        $invoiceDate = $this->normalizeDate($extraction->invoiceDate) ?? $today;
        $receiveDate = $this->normalizeDate($extraction->receiveDate) ?? $today;

        $currency = $extraction->currency ?? 'IDR';
        $amount = $extraction->amount;
        $amountStr = $amount !== null ? number_format($amount, 2, '.', '') : '';

        $remarksParts = [];
        if ($supplierMatch['supplier_id'] === null && $extraction->supplierNameRaw) {
            $remarksParts[] = '[Import] Supplier not matched automatically: '.$extraction->supplierNameRaw;
        }
        if ($extraction->lineItems !== []) {
            $lines = array_map(function ($r) {
                $parts = [trim((string) ($r['description'] ?? ''))];
                if (($r['quantity'] ?? null) !== null) {
                    $parts[] = 'qty '.number_format((float) $r['quantity'], 4, '.', '');
                }
                if (($r['unit_price'] ?? null) !== null) {
                    $parts[] = '@ '.number_format((float) $r['unit_price'], 4, '.', '');
                }
                if (($r['amount'] ?? null) !== null) {
                    $parts[] = '= '.number_format((float) $r['amount'], 2, '.', '');
                }

                return implode(' ', array_filter($parts));
            }, $extraction->lineItems);
            $remarksParts[] = '[Import lines] '.implode('; ', array_filter($lines));
        }

        $invoiceProject = $user->project;
        if ($invoiceProject && ! Project::active()->where('code', $invoiceProject)->exists()) {
            $invoiceProject = null;
        }

        $low = $extraction->lowConfidenceFields;
        foreach (['amount', 'invoice_number', 'supplier_name'] as $f) {
            if ($extraction->confidence < 0.55) {
                $low[] = $f;
            }
        }
        $low = array_values(array_unique(array_filter($low)));

        return [
            'invoice_number' => $extraction->invoiceNumber ?? '',
            'faktur_no' => $extraction->fakturNo ?? '',
            'invoice_date' => $invoiceDate,
            'receive_date' => $receiveDate,
            'po_no' => $extraction->poNo ?? '',
            'currency' => $currency,
            'amount' => $amountStr,
            'amount_display' => $amount !== null ? number_format($amount, 2, '.', ',') : '',
            'supplier_id' => $supplierMatch['supplier_id'],
            'supplier_candidates' => $supplierMatch['candidates'],
            'supplier_name_raw' => $extraction->supplierNameRaw,
            'remarks' => implode("\n", array_filter($remarksParts)),
            'warnings' => $extraction->warnings,
            'low_confidence_fields' => $low,
            'receive_project' => $user->project ?? '',
            'invoice_project' => $invoiceProject ?? '',
            'payment_project' => '001H',
            'cur_loc' => $user->department_location_code ?? '',
            'confidence' => $extraction->confidence,
            'line_items' => array_values(array_map(static function (array $r): array {
                return [
                    'description' => $r['description'] ?? '',
                    'quantity' => $r['quantity'] ?? null,
                    'unit_price' => $r['unit_price'] ?? null,
                    'amount' => $r['amount'] ?? null,
                ];
            }, $extraction->lineItems)),
        ];
    }

    private function normalizeDate(?string $d): ?string
    {
        if ($d === null || trim($d) === '') {
            return null;
        }
        try {
            return Carbon::parse($d)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
