<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    public function import()
    {
        try {
            $apiUrl = config('app.suppliers_sync_url');

            if (!$apiUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suppliers sync URL not configured.'
                ], 400);
            }

            $response = Http::timeout(30)->get($apiUrl);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch suppliers from API. Status: ' . $response->status()
                ], 400);
            }

            $data = $response->json();

            // Debug logging
            Log::info('Suppliers API Response:', $data);
            Log::info('API Response Keys:', ['keys' => array_keys($data)]);
            Log::info('Customers array count:', ['count' => isset($data['customers']) ? count($data['customers']) : 'not set']);
            if (isset($data['customers']) && is_array($data['customers'])) {
                Log::info('First customer sample:', ['customer' => $data['customers'][0] ?? 'no customers']);
            }

            // Check if we have the expected structure
            if (!isset($data['customers']) || !is_array($data['customers'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid API response format. Expected customers array.',
                    'debug_data' => array_keys($data)
                ], 400);
            }

            // Separate vendors and customers based on the 'type' field
            $vendors = [];
            $customers = [];

            foreach ($data['customers'] as $supplier) {
                if (isset($supplier['type'])) {
                    if ($supplier['type'] === 'vendor') {
                        $vendors[] = $supplier;
                    } elseif ($supplier['type'] === 'customer') {
                        $customers[] = $supplier;
                    }
                }
            }

            Log::info('Separated suppliers:', [
                'vendors_count' => count($vendors),
                'customers_count' => count($customers)
            ]);

            $created = 0;
            $skipped = 0;
            $errors = [];

            // Process vendors
            foreach ($vendors as $vendor) {
                try {
                    $result = $this->processSupplier($vendor, 'vendor');
                    if ($result['created']) {
                        $created++;
                    } else {
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Vendor {$vendor['code']}: " . $e->getMessage();
                }
            }

            // Process customers
            foreach ($customers as $customer) {
                try {
                    $result = $this->processSupplier($customer, 'customer');
                    if ($result['created']) {
                        $created++;
                    } else {
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Customer {$customer['code']}: " . $e->getMessage();
                }
            }

            $message = "Import completed successfully. Created: {$created}, Skipped: {$skipped}";
            if (!empty($errors)) {
                $message .= ". Errors: " . count($errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'created' => $created,
                    'skipped' => $skipped,
                    'errors' => $errors
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Supplier import error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processSupplier($supplierData, $type)
    {
        // Check if supplier already exists by SAP code
        if (isset($supplierData['code']) && !empty($supplierData['code'])) {
            $existingSupplier = Supplier::where('sap_code', $supplierData['code'])->first();
            if ($existingSupplier) {
                return ['created' => false, 'message' => 'Supplier already exists'];
            }
        }

        // Create new supplier
        Supplier::create([
            'sap_code' => $supplierData['code'] ?? null,
            'name' => $supplierData['name'],
            'type' => $type,
            'city' => null,
            'payment_project' => '001H', // Default value as per migration
            'is_active' => true,
            'address' => null,
            'npwp' => null,
            'created_by' => Auth::id(),
        ]);

        return ['created' => true, 'message' => 'Supplier created successfully'];
    }
}
