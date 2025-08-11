<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:superadmin|admin');
    }

    public function index()
    {
        return view('admin.projects.index');
    }

    public function data()
    {
        $projects = Project::orderBy('created_at', 'desc')->get();

        return DataTables::of($projects)
            ->addColumn('status', function ($project) {
                if ($project->is_active) {
                    return '<span class="badge badge-success">Active</span>';
                } else {
                    return '<span class="badge badge-danger">Inactive</span>';
                }
            })
            ->addColumn('actions', function ($project) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<a href="' . route('admin.projects.show', $project) . '" class="btn btn-info btn-xs" title="View Project"><i class="fas fa-eye"></i></a>';
                $actions .= '<button type="button" class="btn btn-warning btn-xs edit-project" data-toggle="modal" data-target="#projectModal" data-id="' . $project->id . '" data-code="' . $project->code . '" data-owner="' . ($project->owner ?? '') . '" data-location="' . ($project->location ?? '') . '" data-active="' . ($project->is_active ? '1' : '0') . '" title="Edit Project"><i class="fas fa-edit"></i></button>';
                $actions .= '<button type="button" class="btn btn-danger btn-xs delete-project" data-id="' . $project->id . '" data-name="' . $project->code . '" title="Delete Project"><i class="fas fa-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }



    public function store(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:projects'],
            'owner' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        Project::create([
            'code' => $request->code,
            'owner' => $request->owner,
            'location' => $request->location,
            'is_active' => $request->has('is_active'),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Project created successfully.'
            ]);
        }

        return redirect()->route('admin.projects.index')
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        return view('admin.projects.show', compact('project'));
    }



    public function update(Request $request, Project $project)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:projects,code,' . $project->id],
            'owner' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $project->update([
            'code' => $request->code,
            'owner' => $request->owner,
            'location' => $request->location,
            'is_active' => $request->has('is_active'),
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully.'
            ]);
        }

        return redirect()->route('admin.projects.index')
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully.'
        ]);
    }
}
