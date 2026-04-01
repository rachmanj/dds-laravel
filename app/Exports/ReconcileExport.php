<?php

namespace App\Exports;

use App\Models\ReconcileDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReconcileExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles
{
    protected $reconciles;

    protected $invoiceMap = [];

    public function __construct(Collection $reconciles)
    {
        $this->reconciles = $reconciles;

        // Pre-load all potentially matching invoices with distributions and suppliers
        // to prevent N+1 queries in the map() method
        $invoiceNumbers = $reconciles->pluck('invoice_no')->filter()->unique()->toArray();

        if (! empty($invoiceNumbers)) {
            $invoices = \App\Models\Invoice::where(function ($query) use ($invoiceNumbers) {
                foreach ($invoiceNumbers as $invoiceNo) {
                    $query->orWhere('invoice_number', 'LIKE', '%'.$invoiceNo.'%')
                        ->orWhere('faktur_no', 'LIKE', '%'.$invoiceNo.'%');
                }
            })
                ->with(['distributions' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }, 'supplier'])
                ->get();

            // Create lookup map by invoice number and faktur number
            foreach ($invoices as $invoice) {
                $this->invoiceMap[strtolower($invoice->invoice_number ?? '')] = $invoice;
                if ($invoice->faktur_no) {
                    $this->invoiceMap[strtolower($invoice->faktur_no)] = $invoice;
                }
            }
        }
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->reconciles;
    }

    public function headings(): array
    {
        return [
            'Invoice No (External)',
            'Invoice No (Internal)',
            'Supplier Name',
            'Invoice Date',
            'Receive Date',
            'Amount',
            'SPI No',
            'SPI Date',
            'Distribution Number',
            'Distribution Sent Date',
            'Status',
            'Uploaded Date',
        ];
    }

    /**
     * @param  ReconcileDetail  $reconcile
     */
    public function map($reconcile): array
    {
        // Use pre-loaded invoice map instead of accessor to avoid N+1 queries
        // This ensures distribution numbers are shown even for partial matches
        $matchingInvoice = null;
        if ($reconcile->invoice_no) {
            $invoiceNoLower = strtolower(trim($reconcile->invoice_no));

            // First try exact match
            if (isset($this->invoiceMap[$invoiceNoLower])) {
                $matchingInvoice = $this->invoiceMap[$invoiceNoLower];
            } else {
                // Try to find matching invoice in pre-loaded map using partial match
                foreach ($this->invoiceMap as $key => $invoice) {
                    $keyLower = strtolower(trim($key));
                    // Partial match (either direction) - LIKE pattern matching
                    if (
                        stripos($keyLower, $invoiceNoLower) !== false ||
                        stripos($invoiceNoLower, $keyLower) !== false
                    ) {
                        $matchingInvoice = $invoice;
                        break;
                    }
                }
            }

            // Fallback: if not found in map, use accessor (shouldn't happen but safety check)
            if (! $matchingInvoice) {
                $matchingInvoice = $reconcile->matching_invoice;
            }
        }

        $distributionNumbers = 'N/A';
        $distributionDates = 'N/A';
        if ($matchingInvoice) {
            $directDistributions = \App\Models\Distribution::forInvoiceDocument($matchingInvoice->id);

            if ($directDistributions->isNotEmpty()) {
                $distributionNumbers = $directDistributions
                    ->pluck('distribution_number')
                    ->filter()
                    ->implode(', ');
                $distributionDates = $directDistributions
                    ->map(fn ($d) => $d->local_sent_at
                        ? $d->local_sent_at->format('d-M-Y')
                        : 'N/A')
                    ->implode(', ');
            }
        }

        // Get invoice date - prefer from matched invoice, fallback to reconcile detail
        $invoiceDate = 'N/A';
        if ($matchingInvoice && $matchingInvoice->invoice_date) {
            $invoiceDate = $matchingInvoice->invoice_date->format('d-M-Y');
        } elseif ($reconcile->invoice_date) {
            $invoiceDate = $reconcile->invoice_date->format('d-M-Y');
        }

        return [
            $reconcile->invoice_no,
            $matchingInvoice ? $matchingInvoice->invoice_number : 'No Match',
            $matchingInvoice && $matchingInvoice->supplier ? $matchingInvoice->supplier->name : 'N/A',
            $invoiceDate,
            $matchingInvoice && $matchingInvoice->receive_date ? $matchingInvoice->receive_date->format('d-M-Y') : 'N/A',
            $matchingInvoice ? number_format($matchingInvoice->amount, 2) : 'N/A',
            $matchingInvoice ? $matchingInvoice->sap_doc : 'N/A',
            $matchingInvoice && $matchingInvoice->payment_date ? $matchingInvoice->payment_date->format('d-M-Y') : 'N/A',
            $distributionNumbers,
            $distributionDates,
            ucfirst(str_replace('_', ' ', $reconcile->reconciliation_status)),
            $reconcile->created_at->format('d-M-Y H:i'),
        ];
    }

    /**
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

    public function columnWidths(): array
    {
        return [
            'A' => 25, // Invoice No (External)
            'B' => 25, // Invoice No (Internal)
            'C' => 30, // Supplier Name
            'D' => 15, // Invoice Date
            'E' => 15, // Receive Date
            'F' => 15, // Amount
            'G' => 20, // SPI No
            'H' => 15, // SPI Date
            'I' => 30, // Distribution Number
            'J' => 22, // Distribution Date
            'K' => 15, // Status
            'L' => 20, // Uploaded Date
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Auto-filter for the first row
                $event->sheet->getDelegate()->setAutoFilter('A1:L1');

                // Add summary information
                $rowCount = $this->reconciles->count();
                $matchedCount = $this->reconciles->filter(function ($reconcile) {
                    return $reconcile->reconciliation_status === 'matched';
                })->count();
                $unmatchedCount = $this->reconciles->filter(function ($reconcile) {
                    return $reconcile->reconciliation_status === 'no_match';
                })->count();

                $event->sheet->getDelegate()->setCellValue('A'.($rowCount + 3), 'Summary:');
                $event->sheet->getDelegate()->setCellValue('A'.($rowCount + 4), 'Total Records: '.$rowCount);
                $event->sheet->getDelegate()->setCellValue('A'.($rowCount + 5), 'Matched: '.$matchedCount);
                $event->sheet->getDelegate()->setCellValue('A'.($rowCount + 6), 'Unmatched: '.$unmatchedCount);
                $event->sheet->getDelegate()->setCellValue('A'.($rowCount + 7), 'Generated: '.now()->format('Y-m-d H:i:s'));
            },
        ];
    }
}
