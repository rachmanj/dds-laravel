<?php

namespace App\Exports;

use App\Models\ReconcileDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReconcileExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $reconciles;

    public function __construct(Collection $reconciles)
    {
        $this->reconciles = $reconciles;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->reconciles;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Invoice No (External)',
            'Invoice No (Internal)',
            'Vendor Name',
            'Receive Date',
            'Amount',
            'SPI No',
            'SPI Date',
            'Status',
            'Uploaded Date',
        ];
    }

    /**
     * @param ReconcileDetail $reconcile
     * @return array
     */
    public function map($reconcile): array
    {
        $matchingInvoice = $reconcile->matching_invoice;

        return [
            $reconcile->invoice_no,
            $matchingInvoice ? $matchingInvoice->invoice_number : 'No Match',
            $matchingInvoice && $matchingInvoice->supplier ? $matchingInvoice->supplier->name : 'N/A',
            $matchingInvoice ? $matchingInvoice->receive_date->format('Y-m-d') : 'N/A',
            $matchingInvoice ? number_format($matchingInvoice->amount, 2) : 'N/A',
            $matchingInvoice ? $matchingInvoice->sap_doc : 'N/A',
            $matchingInvoice && $matchingInvoice->payment_date ? $matchingInvoice->payment_date->format('Y-m-d') : 'N/A',
            ucfirst(str_replace('_', ' ', $reconcile->reconciliation_status)),
            $reconcile->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'E3F2FD',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 25, // Invoice No (External)
            'B' => 25, // Invoice No (Internal)
            'C' => 30, // Vendor Name
            'D' => 15, // Receive Date
            'E' => 15, // Amount
            'F' => 20, // SPI No
            'G' => 15, // SPI Date
            'H' => 15, // Status
            'I' => 20, // Uploaded Date
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Auto-filter for the first row
                $event->sheet->getDelegate()->setAutoFilter('A1:I1');

                // Add summary information
                $rowCount = $this->reconciles->count();
                $matchedCount = $this->reconciles->filter(function ($reconcile) {
                    return $reconcile->reconciliation_status === 'matched';
                })->count();
                $unmatchedCount = $this->reconciles->filter(function ($reconcile) {
                    return $reconcile->reconciliation_status === 'no_match';
                })->count();

                $event->sheet->getDelegate()->setCellValue('A' . ($rowCount + 3), 'Summary:');
                $event->sheet->getDelegate()->setCellValue('A' . ($rowCount + 4), 'Total Records: ' . $rowCount);
                $event->sheet->getDelegate()->setCellValue('A' . ($rowCount + 5), 'Matched: ' . $matchedCount);
                $event->sheet->getDelegate()->setCellValue('A' . ($rowCount + 6), 'Unmatched: ' . $unmatchedCount);
                $event->sheet->getDelegate()->setCellValue('A' . ($rowCount + 7), 'Generated: ' . now()->format('Y-m-d H:i:s'));
            },
        ];
    }
}
