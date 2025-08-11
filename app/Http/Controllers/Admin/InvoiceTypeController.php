<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class InvoiceTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:superadmin|admin');
    }

    public function index()
    {
        return view('admin.invoice_types.index');
    }

    public function data()
    {
        $invoiceTypes = InvoiceType::orderBy('created_at', 'desc')->get();

        return DataTables::of($invoiceTypes)
            ->addColumn('status', function ($invoiceType) {
                if ($invoiceType->type_name) {
                    return '<span class="badge badge-success">Active</span>';
                } else {
                    return '<span class="badge badge-danger">Inactive</span>';
                }
            })
            ->addColumn('actions', function ($invoiceType) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<a href="' . route('admin.invoice-types.show', $invoiceType) . '" class="btn btn-info btn-xs" title="View Invoice Type"><i class="fas fa-eye"></i></a>';
                $actions .= '<button type="button" class="btn btn-warning btn-xs edit-invoice-type" data-toggle="modal" data-target="#invoiceTypeModal" data-id="' . $invoiceType->id . '" data-type-name="' . ($invoiceType->type_name ?? '') . '" title="Edit Invoice Type"><i class="fas fa-edit"></i></button>';
                $actions .= '<button type="button" class="btn btn-danger btn-xs delete-invoice-type" data-id="' . $invoiceType->id . '" data-name="' . $invoiceType->type_name . '" title="Delete Invoice Type"><i class="fas fa-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type_name' => ['required', 'string', 'max:255', 'unique:invoice_types'],
        ]);

        InvoiceType::create([
            'type_name' => $request->type_name,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Invoice Type created successfully.'
            ]);
        }

        return redirect()->route('admin.invoice-types.index')
            ->with('success', 'Invoice Type created successfully.');
    }

    public function show(InvoiceType $invoiceType)
    {
        return view('admin.invoice_types.show', compact('invoiceType'));
    }

    public function update(Request $request, InvoiceType $invoiceType)
    {
        $request->validate([
            'type_name' => ['required', 'string', 'max:255', 'unique:invoice_types,type_name,' . $invoiceType->id],
        ]);

        $invoiceType->update([
            'type_name' => $request->type_name,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Invoice Type updated successfully.'
            ]);
        }

        return redirect()->route('admin.invoice-types.index')
            ->with('success', 'Invoice Type updated successfully.');
    }

    public function destroy(InvoiceType $invoiceType)
    {
        $invoiceType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice Type deleted successfully.'
        ]);
    }
}
