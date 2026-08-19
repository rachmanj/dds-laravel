<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Project;
use App\Models\SignatureSpecimen;
use App\Models\SignatureSpecimenImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class SignatureSpecimenController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage-signature-specimens');
    }

    public function index()
    {
        return view('admin.signature_specimens.index');
    }

    public function data()
    {
        $specimens = SignatureSpecimen::with(['department', 'projects', 'images'])
            ->orderBy('created_at', 'desc');

        return DataTables::of($specimens)
            ->addColumn('department_name', fn (SignatureSpecimen $specimen) => $specimen->department?->name ?? '-')
            ->addColumn('projects_list', function (SignatureSpecimen $specimen) {
                return $specimen->projects->pluck('code')->implode(', ') ?: '-';
            })
            ->addColumn('image_count', fn (SignatureSpecimen $specimen) => $specimen->images->count())
            ->addColumn('status', function (SignatureSpecimen $specimen) {
                return $specimen->is_active
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-danger">Inactive</span>';
            })
            ->addColumn('actions', function (SignatureSpecimen $specimen) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<a href="'.route('admin.signature-specimens.edit', $specimen).'" class="btn btn-warning btn-xs" title="Edit"><i class="fas fa-edit"></i></a>';
                $actions .= '<button type="button" class="btn btn-danger btn-xs delete-specimen" data-id="'.$specimen->id.'" data-name="'.e($specimen->name).'" title="Delete"><i class="fas fa-trash"></i></button>';
                $actions .= '</div>';

                return $actions;
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $departments = Department::active()->orderBy('location_code')->get();
        $projects = Project::active()->orderBy('code')->get();

        return view('admin.signature_specimens.create', compact('departments', 'projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'project_ids' => ['required', 'array', 'min:1'],
            'project_ids.*' => ['exists:projects,id'],
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png', 'max:51200'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $specimen = SignatureSpecimen::create([
            'name' => $validated['name'],
            'nik' => $validated['nik'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $specimen->projects()->sync($validated['project_ids']);
        $this->storeImages($specimen, $request->file('images', []));

        return redirect()->route('admin.signature-specimens.index')
            ->with('success', 'Signature specimen created successfully.');
    }

    public function edit(SignatureSpecimen $signatureSpecimen)
    {
        $signatureSpecimen->load(['department', 'projects', 'images']);
        $departments = Department::active()->orderBy('location_code')->get();
        $projects = Project::active()->orderBy('code')->get();

        return view('admin.signature_specimens.edit', compact('signatureSpecimen', 'departments', 'projects'));
    }

    public function update(Request $request, SignatureSpecimen $signatureSpecimen)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'project_ids' => ['required', 'array', 'min:1'],
            'project_ids.*' => ['exists:projects,id'],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png', 'max:51200'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $signatureSpecimen->update([
            'name' => $validated['name'],
            'nik' => $validated['nik'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $signatureSpecimen->projects()->sync($validated['project_ids']);

        if ($request->hasFile('images')) {
            $this->storeImages($signatureSpecimen, $request->file('images', []));
        }

        return redirect()->route('admin.signature-specimens.index')
            ->with('success', 'Signature specimen updated successfully.');
    }

    public function destroy(SignatureSpecimen $signatureSpecimen)
    {
        foreach ($signatureSpecimen->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $signatureSpecimen->delete();

        return response()->json([
            'success' => true,
            'message' => 'Signature specimen deleted successfully.',
        ]);
    }

    public function destroyImage(SignatureSpecimen $signatureSpecimen, SignatureSpecimenImage $image)
    {
        if ($image->specimen_id !== $signatureSpecimen->id) {
            abort(404);
        }

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Specimen image deleted successfully.',
        ]);
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     */
    private function storeImages(SignatureSpecimen $specimen, array $files): void
    {
        foreach ($files as $file) {
            $fileName = time().'_'.uniqid().'_'.$file->getClientOriginalName();
            $path = $file->storeAs('signature-specimens', $fileName, 'public');

            SignatureSpecimenImage::create([
                'specimen_id' => $specimen->id,
                'path' => $path,
            ]);
        }
    }
}
