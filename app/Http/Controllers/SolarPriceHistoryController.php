<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSolarPriceHistoryRequest;
use App\Http\Requests\UpdateSolarPriceHistoryRequest;
use App\Models\Invoice;
use App\Models\SolarPriceHistory;
use App\Services\PertaminaSolarInvoiceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SolarPriceHistoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:create-solar-price-histories')
            ->only(['create', 'store', 'fetchLastPertaminaSolar', 'solarLinesForInvoice']);
        $this->middleware('can:edit-solar-price-histories')->only(['edit', 'update']);
        $this->middleware('can:delete-solar-price-histories')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $query = SolarPriceHistory::query()
            ->with(['invoice.supplier', 'invoiceLineDetail', 'creator'])
            ->orderByDesc('period_start')
            ->orderByDesc('id');

        if ($request->filled('filter_invoice_number')) {
            $term = $request->input('filter_invoice_number');
            $query->whereHas('invoice', function ($q) use ($term): void {
                $q->where('invoice_number', 'like', '%'.$term.'%');
            });
        }

        $overlapFrom = $request->input('filter_period_overlap_from');
        $overlapTo = $request->input('filter_period_overlap_to');
        if ($request->filled('filter_period_overlap_from') && $request->filled('filter_period_overlap_to')) {
            if (strcmp($overlapFrom, $overlapTo) > 0) {
                [$overlapFrom, $overlapTo] = [$overlapTo, $overlapFrom];
            }
            $query->whereRaw(
                'solar_price_histories.period_start <= ? AND solar_price_histories.period_end >= ?',
                [$overlapTo, $overlapFrom]
            );
        } elseif ($request->filled('filter_period_overlap_from')) {
            $query->whereDate('period_end', '>=', $overlapFrom);
        } elseif ($request->filled('filter_period_overlap_to')) {
            $query->whereDate('period_start', '<=', $overlapTo);
        }

        $histories = $query->get();
        $pertaminaId = \App\Models\Supplier::query()
            ->where('name', PertaminaSolarInvoiceResolver::PERTAMINA_SUPPLIER_NAME)
            ->value('id');

        $year = (int) now()->year;
        $solarForYear = SolarPriceHistory::query()
            ->where('period_start', '<=', $year.'-12-31')
            ->where('period_end', '>=', $year.'-01-01')
            ->orderBy('period_start')
            ->orderBy('id')
            ->get();

        $solarUnitPriceYearChart = [
            'year' => $year,
            'labels' => $solarForYear
                ->map(fn (SolarPriceHistory $r) => $r->period_start?->format('M j') ?? '')
                ->values()
                ->all(),
            'values' => $solarForYear
                ->map(fn (SolarPriceHistory $r) => (float) $r->unit_price)
                ->values()
                ->all(),
        ];

        return view('solar_price_histories.index', compact('histories', 'pertaminaId', 'solarUnitPriceYearChart'));
    }

    public function create(): View
    {
        $pertaminaId = \App\Models\Supplier::query()
            ->where('name', PertaminaSolarInvoiceResolver::PERTAMINA_SUPPLIER_NAME)
            ->value('id');

        $invoices = $pertaminaId
            ? Invoice::query()
                ->where('supplier_id', $pertaminaId)
                ->orderByDesc('id')
                ->limit(300)
                ->get()
            : collect();

        return view('solar_price_histories.create', compact('invoices', 'pertaminaId'));
    }

    public function store(StoreSolarPriceHistoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();
        SolarPriceHistory::query()->create($data);

        return redirect()
            ->route('solar-price-histories.index')
            ->with('success', 'Solar price history saved.');
    }

    public function show(SolarPriceHistory $solarPriceHistory): View
    {
        $solarPriceHistory->load(['invoice.supplier', 'invoiceLineDetail', 'creator']);

        return view('solar_price_histories.show', compact('solarPriceHistory'));
    }

    public function edit(SolarPriceHistory $solarPriceHistory): View
    {
        $pertaminaId = \App\Models\Supplier::query()
            ->where('name', PertaminaSolarInvoiceResolver::PERTAMINA_SUPPLIER_NAME)
            ->value('id');

        $invoices = $pertaminaId
            ? Invoice::query()
                ->where('supplier_id', $pertaminaId)
                ->orderByDesc('id')
                ->limit(300)
                ->get()
            : collect();

        $solarPriceHistory->load(['invoice', 'invoiceLineDetail']);

        return view('solar_price_histories.edit', compact('solarPriceHistory', 'invoices', 'pertaminaId'));
    }

    public function update(UpdateSolarPriceHistoryRequest $request, SolarPriceHistory $solarPriceHistory): RedirectResponse
    {
        $solarPriceHistory->update($request->validated());

        return redirect()
            ->route('solar-price-histories.index')
            ->with('success', 'Solar price history updated.');
    }

    public function destroy(SolarPriceHistory $solarPriceHistory): RedirectResponse
    {
        $solarPriceHistory->delete();

        return redirect()
            ->route('solar-price-histories.index')
            ->with('success', 'Solar price history deleted.');
    }

    public function invoicePreview(SolarPriceHistory $solarPriceHistory): JsonResponse
    {
        $solarPriceHistory->load(['invoice.supplier', 'invoice.type', 'invoiceLineDetail']);

        $invoice = $solarPriceHistory->invoice;
        if (! $invoice) {
            return response()->json(['message' => 'Invoice not found.'], 404);
        }

        $line = $solarPriceHistory->invoiceLineDetail;

        $user = Auth::user();

        return response()->json([
            'open_url' => ($user && $user->can('view-invoices'))
                ? route('invoices.show', $invoice)
                : null,
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'faktur_no' => $invoice->faktur_no,
                'invoice_date' => $invoice->invoice_date?->toDateString(),
                'receive_date' => $invoice->receive_date?->toDateString(),
                'po_no' => $invoice->po_no,
                'currency' => $invoice->currency,
                'amount' => (string) $invoice->amount,
                'status' => $invoice->status,
                'cur_loc' => $invoice->cur_loc,
                'payment_status' => $invoice->payment_status,
                'sap_doc' => $invoice->sap_doc,
                'remarks' => $invoice->remarks,
                'supplier' => $invoice->supplier
                    ? [
                        'name' => $invoice->supplier->name,
                        'sap_code' => $invoice->supplier->sap_code,
                    ]
                    : null,
                'type' => $invoice->type?->type_name,
            ],
            'line' => $line
                ? [
                    'id' => $line->id,
                    'line_no' => $line->line_no,
                    'description' => $line->description,
                    'quantity' => $line->quantity !== null ? (string) $line->quantity : null,
                    'unit_price' => $line->unit_price !== null ? (string) $line->unit_price : null,
                    'amount' => $line->amount !== null ? (string) $line->amount : null,
                    'source' => $line->source,
                ]
                : null,
        ]);
    }

    public function fetchLastPertaminaSolar(PertaminaSolarInvoiceResolver $resolver): JsonResponse
    {
        $resolved = $resolver->resolveLast();

        if (! $resolved) {
            return response()->json([
                'message' => 'No PERTAMINA invoice with a SOLAR line was found.',
            ], 404);
        }

        $line = $resolved['line'];
        $unitPrice = $resolved['unit_price'] ?? null;

        if ($unitPrice === null) {
            return response()->json([
                'message' => 'The matched line has no unit price and amount/quantity could not derive one.',
            ], 422);
        }

        $invoice = $resolved['invoice'];

        return response()->json([
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'faktur_no' => $invoice->faktur_no,
                'invoice_date' => $invoice->invoice_date?->toDateString(),
                'receive_date' => $invoice->receive_date?->toDateString(),
                'po_no' => $invoice->po_no,
                'currency' => $invoice->currency,
                'amount' => (string) $invoice->amount,
            ],
            'line' => [
                'id' => $line->id,
                'line_no' => $line->line_no,
                'description' => $line->description,
                'quantity' => $line->quantity !== null ? (string) $line->quantity : null,
                'unit_price' => $line->unit_price !== null ? (string) $line->unit_price : null,
                'amount' => $line->amount !== null ? (string) $line->amount : null,
                'source' => $line->source,
            ],
            'resolved_unit_price' => (string) $unitPrice,
        ]);
    }

    public function solarLinesForInvoice(Invoice $invoice): JsonResponse
    {
        if ($invoice->supplier?->name !== PertaminaSolarInvoiceResolver::PERTAMINA_SUPPLIER_NAME) {
            return response()->json(['message' => 'Invoice is not from PERTAMINA.'], 422);
        }

        $lines = $invoice->lineDetails()
            ->where('description', 'like', '%SOLAR%')
            ->orderBy('line_no')
            ->get(['id', 'line_no', 'description', 'quantity', 'unit_price', 'amount', 'source']);

        return response()->json(['lines' => $lines]);
    }
}
