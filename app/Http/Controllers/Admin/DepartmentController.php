<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:superadmin|admin');
    }

    public function index()
    {
        return view('admin.departments.index');
    }

    public function data()
    {
        $departments = Department::orderBy('created_at', 'desc')->get();

        return DataTables::of($departments)
            ->addColumn('status', function ($department) {
                if ($department->name) {
                    return '<span class="badge badge-success">Active</span>';
                } else {
                    return '<span class="badge badge-danger">Inactive</span>';
                }
            })
            ->addColumn('actions', function ($department) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<a href="' . route('admin.departments.show', $department) . '" class="btn btn-info btn-xs" title="View Department"><i class="fas fa-eye"></i></a>';
                $actions .= '<button type="button" class="btn btn-warning btn-xs edit-department" data-toggle="modal" data-target="#departmentModal" data-id="' . $department->id . '" data-name="' . ($department->name ?? '') . '" data-project="' . ($department->project ?? '') . '" data-location-code="' . ($department->location_code ?? '') . '" data-transit-code="' . ($department->transit_code ?? '') . '" data-akronim="' . ($department->akronim ?? '') . '" data-sap-code="' . ($department->sap_code ?? '') . '" title="Edit Department"><i class="fas fa-edit"></i></button>';
                $actions .= '<button type="button" class="btn btn-danger btn-xs delete-department" data-id="' . $department->id . '" data-name="' . $department->akronim . '" title="Delete Department"><i class="fas fa-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'project' => ['nullable', 'string', 'max:10'],
            'location_code' => ['nullable', 'string', 'max:30'],
            'transit_code' => ['nullable', 'string', 'max:30'],
            'akronim' => ['required', 'string', 'max:20', 'unique:departments'],
            'sap_code' => ['nullable', 'string', 'max:20'],
        ]);

        Department::create([
            'name' => $request->name,
            'project' => $request->project,
            'location_code' => $request->location_code,
            'transit_code' => $request->transit_code,
            'akronim' => $request->akronim,
            'sap_code' => $request->sap_code,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Department created successfully.'
            ]);
        }

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function show(Department $department)
    {
        return view('admin.departments.show', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'project' => ['nullable', 'string', 'max:10'],
            'location_code' => ['nullable', 'string', 'max:30'],
            'transit_code' => ['nullable', 'string', 'max:30'],
            'akronim' => ['required', 'string', 'max:20', 'unique:departments,akronim,' . $department->id],
            'sap_code' => ['nullable', 'string', 'max:20'],
        ]);

        $department->update([
            'name' => $request->name,
            'project' => $request->project,
            'location_code' => $request->location_code,
            'transit_code' => $request->transit_code,
            'akronim' => $request->akronim,
            'sap_code' => $request->sap_code,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Department updated successfully.'
            ]);
        }

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully.'
        ]);
    }
}
