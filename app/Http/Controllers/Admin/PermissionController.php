<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:superadmin|admin');
    }

    public function index()
    {
        return view('admin.permissions.index');
    }



    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions'],
        ]);

        Permission::create(['name' => $request->name, 'guard_name' => $request->guard_name ?? 'web']);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully.'
            ]);
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    public function show(Permission $permission)
    {
        return view('admin.permissions.show', compact('permission'));
    }



    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name,' . $permission->id],
        ]);

        $permission->update(['name' => $request->name, 'guard_name' => $request->guard_name ?? 'web']);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully.'
            ]);
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully.'
            ]);
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }

    public function data()
    {
        $permissions = Permission::orderBy('name', 'asc')->get();

        return DataTables::of($permissions)
            ->addColumn('formatted_name', function ($permission) {
                return '<span class="badge badge-info">' . ucfirst(str_replace('-', ' ', $permission->name)) . '</span>';
            })
            ->addColumn('actions', function ($permission) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<a href="' . route('admin.permissions.show', $permission) . '" class="btn btn-info btn-xs" title="View Permission"><i class="fas fa-eye"></i></a>';
                $actions .= '<button type="button" class="btn btn-warning btn-xs edit-permission" data-toggle="modal" data-target="#permissionModal" data-id="' . $permission->id . '" data-name="' . $permission->name . '" data-guard="' . $permission->guard_name . '" title="Edit Permission"><i class="fas fa-edit"></i></button>';
                $actions .= '<button type="button" class="btn btn-danger btn-xs delete-permission" data-id="' . $permission->id . '" data-name="' . $permission->name . '" title="Delete Permission"><i class="fas fa-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['formatted_name', 'actions'])
            ->make(true);
    }
}
