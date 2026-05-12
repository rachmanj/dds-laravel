<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportVendorInvoicesRequest;
use App\Http\Requests\Admin\LookupVendorInvoicesRequest;
use App\Models\Supplier;
use App\Services\VendorInvoiceFetchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VendorInvoiceImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('role_or_permission:superadmin|admin|view-suppliers')->only(['index']);
        $this->middleware('role_or_permission:superadmin|admin|accounting|create-invoices')->only(['lookup', 'import']);
    }

    public function index(Supplier $supplier): View
    {
        $this->assertVendorConfigured($supplier);

        return view('admin.suppliers.vendor-invoice-import', [
            'supplier' => $supplier,
            'results' => null,
            'invoice_numbers' => old('invoice_numbers', ''),
        ]);
    }

    public function lookup(LookupVendorInvoicesRequest $request, Supplier $supplier, VendorInvoiceFetchService $fetchService): View|RedirectResponse
    {
        $this->assertVendorConfigured($supplier);

        try {
            $invoiceNos = VendorInvoiceFetchService::parseInvoiceNumbers($request->validated('invoice_numbers'));
            $results = $fetchService->lookup((string) $supplier->sap_code, $invoiceNos);
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('warning', $e->getMessage());
        }

        return view('admin.suppliers.vendor-invoice-import', [
            'supplier' => $supplier,
            'results' => $results,
            'invoice_numbers' => $request->validated('invoice_numbers'),
        ]);
    }

    public function import(ImportVendorInvoicesRequest $request, Supplier $supplier, VendorInvoiceFetchService $fetchService): RedirectResponse
    {
        $this->assertVendorConfigured($supplier);

        try {
            $summary = $fetchService->importSelected(
                (string) $supplier->sap_code,
                $request->validated('invoice_nos'),
                (int) $request->user()->id,
                $request->user()->department_location_code
            );
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('admin.suppliers.vendor-invoices.index', $supplier)
                ->with('warning', $e->getMessage());
        }

        $message = sprintf(
            'Imported %d invoice(s). Skipped %d duplicate(s).',
            $summary['imported'],
            $summary['skipped']
        );

        if ($summary['errors'] !== []) {
            $message .= ' Errors: '.implode(' ', $summary['errors']);
        }

        return redirect()
            ->route('admin.suppliers.vendor-invoices.index', $supplier)
            ->with($summary['errors'] === [] ? 'success' : 'warning', $message);
    }

    private function assertVendorConfigured(Supplier $supplier): void
    {
        $sapCode = $supplier->sap_code;
        if ($sapCode === null || $sapCode === '') {
            abort(404);
        }

        if (! array_key_exists($sapCode, config('vendor_api.vendors', []))) {
            abort(404);
        }
    }
}
