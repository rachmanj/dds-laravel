<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class InvoiceDateGuard
{
    private const MIN_INVOICE_DATE = '2000-01-01';

    /**
     * @return list<string>
     */
    public static function check(?string $invoiceDate, ?string $receiveDate): array
    {
        $invoice = self::parseDate($invoiceDate);
        $receive = self::parseDate($receiveDate);

        if ($invoice === null || $receive === null) {
            return [];
        }

        $messages = [];
        $minDate = Carbon::parse(self::MIN_INVOICE_DATE)->startOfDay();

        if ($invoice->lt($minDate)) {
            $messages[] = sprintf(
                'Tanggal invoice (%s) tidak boleh sebelum %s.',
                self::formatDisplay($invoice),
                self::formatDisplay($minDate)
            );
        }

        if ($invoice->lt($receive->copy()->subMonths(24))) {
            $messages[] = sprintf(
                'Tanggal invoice (%s) %s sebelum tanggal terima (%s). Mohon periksa kembali tahun pada dokumen invoice.',
                self::formatDisplay($invoice),
                self::describeMonthsBefore($invoice, $receive),
                self::formatDisplay($receive)
            );
        }

        if ($invoice->gt($receive->copy()->addMonths(12))) {
            $messages[] = sprintf(
                'Tanggal invoice (%s) %s sesudah tanggal terima (%s). Mohon periksa kembali tanggal pada dokumen invoice.',
                self::formatDisplay($invoice),
                self::describeMonthsAfter($invoice, $receive),
                self::formatDisplay($receive)
            );
        }

        return $messages;
    }

    /**
     * @return list<string>
     */
    public static function warn(?string $invoiceDate, ?string $receiveDate): array
    {
        $invoice = self::parseDate($invoiceDate);
        $receive = self::parseDate($receiveDate);

        if ($invoice === null || $receive === null) {
            return [];
        }

        if ($invoice->lt($receive->copy()->subMonths(6))) {
            return [
                sprintf(
                    'Tanggal invoice (%s) %s sebelum tanggal terima (%s). Mohon periksa kembali tahun pada dokumen invoice.',
                    self::formatDisplay($invoice),
                    self::describeMonthsBefore($invoice, $receive),
                    self::formatDisplay($receive)
                ),
            ];
        }

        return [];
    }

    private static function parseDate(?string $value): ?CarbonInterface
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function formatDisplay(CarbonInterface $date): string
    {
        return $date->format('d-m-Y');
    }

    private static function describeMonthsBefore(CarbonInterface $invoice, CarbonInterface $receive): string
    {
        $months = (int) round(max(1, $invoice->diffInMonths($receive)));

        return self::describeDurationInIndonesian($months);
    }

    private static function describeMonthsAfter(CarbonInterface $invoice, CarbonInterface $receive): string
    {
        $months = (int) round(max(1, $receive->diffInMonths($invoice)));

        return self::describeDurationInIndonesian($months);
    }

    private static function describeDurationInIndonesian(int $months): string
    {
        if ($months >= 12) {
            $years = intdiv($months, 12);

            return $years === 1 ? 'lebih dari 1 tahun' : "lebih dari {$years} tahun";
        }

        return "lebih dari {$months} bulan";
    }
}
