<?php

namespace App\Http\Controllers;

use App\Exports\AdditionalDocumentExport;
use App\Exports\InvoiceExport;
use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Project;
use App\Models\Supplier;
use App\Services\DocumentJourneyService;
use App\Support\AdditionalDocumentListFilters;
use App\Support\InvoiceListFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ReportsDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function invoicesIndex(): View
    {
        $invoiceTypes = InvoiceType::orderBy('type_name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $projects = Project::active()->orderBy('code')->get();
        $departments = Department::active()->orderBy('location_code')->get();

        return view('reports.document-report.invoices-index', compact(
            'invoiceTypes',
            'suppliers',
            'projects',
            'departments'
        ));
    }

    public function additionalDocumentsIndex(): View
    {
        $documentTypes = AdditionalDocumentType::orderByName()->get();
        $vendorCodes = AdditionalDocument::whereNotNull('vendor_code')
            ->distinct()
            ->pluck('vendor_code')
            ->sort()
            ->values();
        $departments = Department::active()->orderBy('location_code')->get();

        return view('reports.document-report.additional-documents-index', compact(
            'documentTypes',
            'vendorCodes',
            'departments'
        ));
    }

    public function invoicesData(Request $request)
    {
        $query = InvoiceListFilters::baseQuery();
        InvoiceListFilters::apply($request, $query);

        return DataTables::of($query)
            ->orderColumn('days_difference', 'days_in_location $1')
            ->addColumn('supplier_name', fn ($invoice) => $invoice->supplier?->name ?? '-')
            ->addColumn('type_name', fn ($invoice) => $invoice->type?->type_name ?? '-')
            ->addColumn('formatted_invoice_date', fn ($invoice) => $invoice->formatted_invoice_date)
            ->addColumn('formatted_receive_date', fn ($invoice) => $invoice->formatted_receive_date)
            ->addColumn('formatted_amount', fn ($invoice) => $invoice->formatted_amount)
            ->addColumn('status_badge', fn ($invoice) => $invoice->status_badge)
            ->addColumn('sap_status_badge', fn ($invoice) => $invoice->sap_status_badge)
            ->addColumn('distribution_status_badge', function ($invoice) {
                $status = $invoice->distribution_status ?? 'available';

                return match ($status) {
                    'available' => '<span class="badge badge-success">Available</span>',
                    'in_transit' => '<span class="badge badge-warning">In Transit</span>',
                    'distributed' => '<span class="badge badge-info">Distributed</span>',
                    'unaccounted_for' => '<span class="badge badge-danger">Unaccounted</span>',
                    default => '<span class="badge badge-secondary">'.ucfirst($status).'</span>',
                };
            })
            ->addColumn('days_difference', function ($invoice) {
                return $this->formatDaysBadge($invoice->days_in_location ?? $invoice->days_in_current_location);
            })
            ->addColumn('actions', function ($invoice) {
                return '<a href="'.route('document-report.invoice-detail', $invoice).'" class="btn btn-info btn-xs" title="View Details"><i class="fas fa-eye"></i></a>';
            })
            ->rawColumns(['status_badge', 'sap_status_badge', 'distribution_status_badge', 'days_difference', 'actions'])
            ->make(true);
    }

    public function additionalDocumentsData(Request $request)
    {
        $query = AdditionalDocumentListFilters::baseQuery();
        AdditionalDocumentListFilters::apply($request, $query, null, false);

        return DataTables::of($query)
            ->addIndexColumn()
            ->orderColumn('days_difference', 'days_in_location $1')
            ->addColumn('type_name', fn ($document) => $document->type?->type_name ?? '-')
            ->addColumn('formatted_document_date', fn ($document) => $document->document_date?->format('d/m/Y') ?? '-')
            ->addColumn('formatted_receive_date', fn ($document) => $document->receive_date?->format('d/m/Y') ?? '-')
            ->addColumn('distribution_status_badge', function ($document) {
                $status = $document->distribution_status ?? 'available';

                return match ($status) {
                    'available' => '<span class="badge badge-success">Available</span>',
                    'in_transit' => '<span class="badge badge-warning">In Transit</span>',
                    'distributed' => '<span class="badge badge-info">Distributed</span>',
                    'unaccounted_for' => '<span class="badge badge-danger">Unaccounted</span>',
                    default => '<span class="badge badge-secondary">'.ucfirst($status).'</span>',
                };
            })
            ->addColumn('days_difference', function ($document) {
                return $this->formatDaysBadge($document->days_in_location ?? $document->days_in_current_location);
            })
            ->addColumn('invoice_numbers', function ($document) {
                if ($document->invoices && $document->invoices->count() > 0) {
                    return '<small class="text-muted">'.implode(', ', $document->invoices->pluck('invoice_number')->toArray()).'</small>';
                }

                return '<span class="text-muted">-</span>';
            })
            ->addColumn('actions', function ($document) {
                return '<a href="'.route('document-report.additional-document-detail', $document).'" class="btn btn-info btn-xs" title="View Details"><i class="fas fa-eye"></i></a>';
            })
            ->rawColumns(['invoice_numbers', 'days_difference', 'distribution_status_badge', 'actions'])
            ->make(true);
    }

    public function invoiceDetail(Invoice $invoice, DocumentJourneyService $journeyService): View
    {
        $invoice->load([
            'supplier',
            'type',
            'creator',
            'attachments.uploader',
            'additionalDocuments.type',
            'lineDetails',
            'receiveProjectInfo',
            'invoiceProjectInfo',
            'paymentProjectInfo',
        ]);

        $journey = $journeyService->build('invoice', $invoice->id);

        return view('reports.document-report.invoice-detail', [
            'invoice' => $invoice,
            'journey' => $journey,
        ]);
    }

    public function additionalDocumentDetail(AdditionalDocument $additionalDocument, DocumentJourneyService $journeyService): View
    {
        $additionalDocument->load([
            'type',
            'creator.department',
            'invoices.supplier',
            'invoices.type',
            'signatureProject',
            'signatureCheckedBy',
            'signatureOverrideBy',
        ]);

        $journey = $journeyService->build('additional-document', $additionalDocument->id);

        return view('reports.document-report.additional-document-detail', [
            'additionalDocument' => $additionalDocument,
            'journey' => $journey,
        ]);
    }

    public function exportInvoices(Request $request)
    {
        try {
            $query = InvoiceListFilters::baseQuery();
            InvoiceListFilters::apply($request, $query);

            $invoices = $query->get()->sortByDesc(fn ($invoice) => (float) ($invoice->days_in_location ?? 0))->values();

            $exportData = $invoices->map(fn ($invoice) => [
                'Invoice Number' => $invoice->invoice_number,
                'Faktur No' => $invoice->faktur_no ?? '',
                'Supplier' => $invoice->supplier?->name ?? '',
                'Invoice Type' => $invoice->type?->type_name ?? '',
                'Invoice Date' => $invoice->invoice_date?->format('d/m/Y') ?? '',
                'Receive Date' => $invoice->receive_date?->format('d/m/Y') ?? '',
                'PO Number' => $invoice->po_no ?? '',
                'Amount' => $invoice->amount,
                'Currency' => $invoice->currency,
                'Status' => $invoice->status ?? '',
                'SAP Status' => $invoice->sap_status ?? '',
                'Current Location' => $invoice->cur_loc ?? '',
                'Days in Location' => round((float) ($invoice->days_in_location ?? 0), 1),
                'Distribution Status' => $invoice->distribution_status ?? '',
                'Invoice Project' => $invoice->invoice_project ?? '',
                'Created By' => $invoice->creator?->name ?? '',
                'Created At' => $invoice->created_at?->format('d/m/Y H:i') ?? '',
            ]);

            return Excel::download(
                new InvoiceExport($exportData),
                'document_report_invoices_'.now()->format('Y-m-d_H-i-s').'.xlsx'
            );
        } catch (\Exception $e) {
            Log::error('Document report invoice export failed: '.$e->getMessage());

            return redirect()->back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    public function exportAdditionalDocuments(Request $request)
    {
        try {
            $query = AdditionalDocumentListFilters::baseQuery();
            AdditionalDocumentListFilters::apply($request, $query, null, false);

            $documents = $query->get()->sortByDesc(fn ($document) => (float) ($document->days_in_location ?? 0))->values();

            $exportData = $documents->map(fn ($document) => [
                'Document Number' => $document->document_number,
                'Document Type' => $document->type?->type_name ?? '',
                'Document Date' => $document->document_date?->format('d/m/Y') ?? '',
                'PO Number' => $document->po_no ?? '',
                'Vendor Code' => $document->vendor_code ?? '',
                'Receive Date' => $document->receive_date?->format('d/m/Y') ?? '',
                'Current Location' => $document->cur_loc ?? '',
                'Days in location' => round((float) ($document->days_in_location ?? 0), 1),
                'Status' => $document->status ?? '',
                'Distribution Status' => $document->distribution_status ?? '',
                'Remarks' => $document->remarks ?? '',
                'Created By' => $document->creator?->name ?? '',
                'Created At' => $document->created_at?->format('d/m/Y H:i') ?? '',
            ]);

            return Excel::download(
                new AdditionalDocumentExport($exportData),
                'document_report_additional_documents_'.now()->format('Y-m-d_H-i-s').'.xlsx'
            );
        } catch (\Exception $e) {
            Log::error('Document report additional documents export failed: '.$e->getMessage());

            return redirect()->back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    private function formatDaysBadge(mixed $daysInCurrentLocation): string
    {
        if ($daysInCurrentLocation == 0 || $daysInCurrentLocation === null) {
            return '<span class="text-muted">-</span>';
        }

        $roundedDays = round((float) $daysInCurrentLocation, 1);

        if ($roundedDays <= 7) {
            return '<span class="badge badge-success">'.$roundedDays.'</span>';
        }

        if ($roundedDays <= 14) {
            return '<span class="badge badge-warning">'.$roundedDays.'</span>';
        }

        return '<span class="badge badge-danger">'.$roundedDays.'</span>';
    }
}
