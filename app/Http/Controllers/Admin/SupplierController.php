<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:superadmin|admin');
    }

    public function index()
    {
        $projects = \App\Models\Project::orderBy('code', 'asc')->get();
        return view('admin.suppliers.index', compact('projects'));
    }

    public function data()
    {
        $suppliers = Supplier::with('creator')->orderBy('created_at', 'desc')->get();

        return DataTables::of($suppliers)
            ->addColumn('type_badge', function ($supplier) {
                if ($supplier->type === 'vendor') {
                    return '<span class="badge badge-primary">Vendor</span>';
                } else {
                    return '<span class="badge badge-info">Customer</span>';
                }
            })
            ->addColumn('payment_project_info', function ($supplier) {
                $project = \App\Models\Project::where('code', $supplier->payment_project)->first();
                if ($project) {
                    return $supplier->payment_project . ' - ' . $project->owner;
                }
                return $supplier->payment_project;
            })
            ->addColumn('status', function ($supplier) {
                if ($supplier->is_active) {
                    return '<span class="badge badge-success">Active</span>';
                } else {
                    return '<span class="badge badge-danger">Inactive</span>';
                }
            })
            ->addColumn('actions', function ($supplier) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<a href="' . route('admin.suppliers.show', $supplier) . '" class="btn btn-info btn-xs" title="View Supplier"><i class="fas fa-eye"></i></a>';
                $actions .= '<button type="button" class="btn btn-warning btn-xs edit-supplier" data-toggle="modal" data-target="#supplierModal" data-id="' . $supplier->id . '" data-sap-code="' . ($supplier->sap_code ?? '') . '" data-name="' . $supplier->name . '" data-type="' . $supplier->type . '" data-city="' . ($supplier->city ?? '') . '" data-payment-project="' . $supplier->payment_project . '" data-address="' . ($supplier->address ?? '') . '" data-npwp="' . ($supplier->npwp ?? '') . '" data-active="' . ($supplier->is_active ? '1' : '0') . '" title="Edit Supplier"><i class="fas fa-edit"></i></button>';
                $actions .= '<button type="button" class="btn btn-danger btn-xs delete-supplier" data-id="' . $supplier->id . '" data-name="' . $supplier->name . '" title="Delete Supplier"><i class="fas fa-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['type_badge', 'payment_project_info', 'status', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'sap_code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:vendor,customer'],
            'city' => ['nullable', 'string', 'max:255'],
            'payment_project' => ['required', 'string', 'max:50', 'exists:projects,code'],
            'address' => ['nullable', 'string'],
            'npwp' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $supplier = Supplier::create([
            'sap_code' => $request->sap_code,
            'name' => $request->name,
            'type' => $request->type,
            'city' => $request->city,
            'payment_project' => $request->payment_project,
            'address' => $request->address,
            'npwp' => $request->npwp,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier created successfully.'
            ]);
        }

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    public function show(Supplier $supplier)
    {
        $projects = \App\Models\Project::orderBy('code', 'asc')->get();
        return view('admin.suppliers.show', compact('supplier', 'projects'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'sap_code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:vendor,customer'],
            'city' => ['nullable', 'string', 'max:255'],
            'payment_project' => ['required', 'string', 'max:50', 'exists:projects,code'],
            'address' => ['nullable', 'string'],
            'npwp' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $supplier->update([
            'sap_code' => $request->sap_code,
            'name' => $request->name,
            'type' => $request->type,
            'city' => $request->city,
            'payment_project' => $request->payment_project,
            'address' => $request->address,
            'npwp' => $request->npwp,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier updated successfully.'
            ]);
        }

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully.'
            ]);
        }

        return redirect()->route('admin.suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }
}
