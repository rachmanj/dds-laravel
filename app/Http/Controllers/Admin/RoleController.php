<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:superadmin|admin');
    }

    public function index()
    {
        return view('admin.roles.index');
    }

    public function data()
    {
        $roles = Role::with('permissions')->orderBy('created_at', 'desc')->get();

        return DataTables::of($roles)
            ->addColumn('permissions', function ($role) {
                $permissions = '';
                foreach ($role->permissions->take(3) as $permission) {
                    $permissions .= '<span class="badge badge-info mr-1">' . $permission->name . '</span>';
                }
                if ($role->permissions->count() > 3) {
                    $permissions .= '<span class="badge badge-secondary">+' . ($role->permissions->count() - 3) . ' more</span>';
                }
                return $permissions;
            })
            ->addColumn('users_count', function ($role) {
                return '<span class="badge badge-success">' . ($role->users_count ?? 0) . '</span>';
            })
            ->addColumn('actions', function ($role) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<a href="' . route('admin.roles.show', $role) . '" class="btn btn-info btn-xs" title="View"><i class="fas fa-eye"></i></a>';
                $actions .= '<a href="' . route('admin.roles.edit', $role) . '" class="btn btn-warning btn-xs" title="Edit"><i class="fas fa-edit"></i></a>';

                if ($role->name !== 'superadmin') {
                    $actions .= '<button type="button" class="btn btn-danger btn-xs delete-role" data-id="' . $role->id . '" data-name="' . $role->name . '" title="Delete"><i class="fas fa-trash"></i></button>';
                }
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['permissions', 'users_count', 'actions'])
            ->make(true);
    }



    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
            'permissions' => ['array'],
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions')) {
            // Convert permission IDs to permission names
            $permissionNames = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
            $role->syncPermissions($permissionNames);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function show(Role $role)
    {
        $role->load('permissions');
        return view('admin.roles.show', compact('role'));
    }

    public function create()
    {
        $permissions = Permission::all();
        return view('admin.roles.create', compact('permissions'));
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $role->load('permissions');
        return view('admin.roles.edit', compact('role', 'permissions'));
    }



    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'permissions' => ['array'],
        ]);

        $role->update(['name' => $request->name]);

        // Sync permissions
        if ($request->has('permissions')) {
            // Convert permission IDs to permission names
            $permissionNames = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
            $role->syncPermissions($permissionNames);
        } else {
            $role->syncPermissions([]);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        // Prevent deletion of superadmin role
        if ($role->name === 'superadmin') {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Superadmin role cannot be deleted.'
                ]);
            }
            return redirect()->route('admin.roles.index')
                ->with('error', 'Superadmin role cannot be deleted.');
        }

        $role->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully.'
            ]);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
