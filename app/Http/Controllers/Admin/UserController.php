<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:superadmin|admin');
    }

    public function index()
    {
        return view('admin.users.index');
    }

    public function data()
    {
        $users = User::with(['roles', 'department', 'projectInfo'])->orderBy('created_at', 'desc')->get();

        return DataTables::of($users)
            ->addColumn('roles', function ($user) {
                $roles = '';
                foreach ($user->roles as $role) {
                    $roles .= '<span class="badge badge-primary mr-1">' . $role->name . '</span>';
                }
                return $roles;
            })
            ->addColumn('status', function ($user) {
                if ($user->is_active) {
                    return '<span class="badge badge-success">Active</span>';
                } else {
                    return '<span class="badge badge-secondary">Inactive</span>';
                }
            })
            ->addColumn('project_info', function ($user) {
                if ($user->projectInfo) {
                    return $user->projectInfo->code . ' - ' . $user->projectInfo->owner;
                }
                return $user->project ?: '-';
            })
            ->addColumn('department_location', function ($user) {
                return $user->department_location_code ?: '-';
            })
            ->addColumn('actions', function ($user) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<a href="' . route('admin.users.show', $user) . '" class="btn btn-info btn-xs" title="View User"><i class="fas fa-eye"></i></a>';
                $actions .= '<a href="' . route('admin.users.edit', $user) . '" class="btn btn-warning btn-xs" title="Edit User"><i class="fas fa-edit"></i></a>';

                // Toggle status button
                $statusBtnClass = $user->is_active ? 'btn-secondary' : 'btn-success';
                $statusBtnTitle = $user->is_active ? 'Deactivate User' : 'Activate User';
                $statusBtnIcon = $user->is_active ? 'fa-user-slash' : 'fa-user-check';
                $actions .= '<button type="button" class="btn ' . $statusBtnClass . ' btn-xs toggle-status" data-id="' . $user->id . '" data-name="' . $user->name . '" data-active="' . ($user->is_active ? '1' : '0') . '" title="' . $statusBtnTitle . '"><i class="fas ' . $statusBtnIcon . '"></i></button>';

                if ($user->id !== \Illuminate\Support\Facades\Auth::id()) {
                    $actions .= '<button type="button" class="btn btn-danger btn-xs delete-user" data-id="' . $user->id . '" data-name="' . $user->name . '" title="Delete User"><i class="fas fa-trash"></i></button>';
                }
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['roles', 'status', 'project_info', 'department_location', 'actions'])
            ->make(true);
    }



    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'nik' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'project' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nik' => $request->nik,
            'username' => $request->username,
            'project' => $request->project,
            'department_id' => $request->department_id,
            'is_active' => $request->has('is_active'), // Admin can set active status
        ]);

        if ($request->has('roles')) {
            // Convert role IDs to role names
            $roleNames = Role::whereIn('id', $request->roles)->pluck('name')->toArray();
            $user->assignRole($roleNames);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load('roles', 'permissions');
        return view('admin.users.show', compact('user'));
    }

    public function create()
    {
        $roles = Role::all();
        $departments = \App\Models\Department::orderBy('name', 'asc')->get();
        $projects = \App\Models\Project::active()->get();
        return view('admin.users.create', compact('roles', 'departments', 'projects'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $departments = \App\Models\Department::orderBy('name', 'asc')->get();
        $projects = \App\Models\Project::active()->get();
        $user->load('roles');
        return view('admin.users.edit', compact('user', 'roles', 'departments', 'projects'));
    }



    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'nik' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'project' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'nik' => $request->nik,
            'username' => $request->username,
            'project' => $request->project,
            'department_id' => $request->department_id,
            'is_active' => $request->has('is_active'),
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        // Sync roles
        if ($request->has('roles')) {
            // Convert role IDs to role names
            $roleNames = Role::whereIn('id', $request->roles)->pluck('name')->toArray();
            $user->syncRoles($roleNames);
        } else {
            $user->syncRoles([]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        // Prevent self-deletion
        if ($user->id === \Illuminate\Support\Facades\Auth::id()) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account.'
                ]);
            }
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.'
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "User {$status} successfully."
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "User {$status} successfully.");
    }
}
