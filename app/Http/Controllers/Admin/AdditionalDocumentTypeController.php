<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdditionalDocumentType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AdditionalDocumentTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:superadmin|admin');
    }

    public function index()
    {
        return view('admin.additional_document_types.index');
    }

    public function data()
    {
        $additionalDocumentTypes = AdditionalDocumentType::orderBy('created_at', 'desc')->get();

        return DataTables::of($additionalDocumentTypes)
            ->addColumn('status', function ($additionalDocumentType) {
                if ($additionalDocumentType->type_name) {
                    return '<span class="badge badge-success">Active</span>';
                } else {
                    return '<span class="badge badge-danger">Inactive</span>';
                }
            })
            ->addColumn('actions', function ($additionalDocumentType) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<a href="' . route('admin.additional-document-types.show', $additionalDocumentType) . '" class="btn btn-info btn-xs" title="View Document Type"><i class="fas fa-eye"></i></a>';
                $actions .= '<button type="button" class="btn btn-warning btn-xs edit-additional-document-type" data-toggle="modal" data-target="#additionalDocumentTypeModal" data-id="' . $additionalDocumentType->id . '" data-type-name="' . ($additionalDocumentType->type_name ?? '') . '" title="Edit Document Type"><i class="fas fa-edit"></i></button>';
                $actions .= '<button type="button" class="btn btn-danger btn-xs delete-additional-document-type" data-id="' . $additionalDocumentType->id . '" data-name="' . $additionalDocumentType->type_name . '" title="Delete Document Type"><i class="fas fa-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type_name' => ['required', 'string', 'max:255', 'unique:additional_document_types'],
        ]);

        AdditionalDocumentType::create([
            'type_name' => $request->type_name,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Additional Document Type created successfully.'
            ]);
        }

        return redirect()->route('admin.additional-document-types.index')
            ->with('success', 'Additional Document Type created successfully.');
    }

    public function show(AdditionalDocumentType $additionalDocumentType)
    {
        return view('admin.additional_document_types.show', compact('additionalDocumentType'));
    }

    public function update(Request $request, AdditionalDocumentType $additionalDocumentType)
    {
        $request->validate([
            'type_name' => ['required', 'string', 'max:255', 'unique:additional_document_types,type_name,' . $additionalDocumentType->id],
        ]);

        $additionalDocumentType->update([
            'type_name' => $request->type_name,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Additional Document Type updated successfully.'
            ]);
        }

        return redirect()->route('admin.additional-document-types.index')
            ->with('success', 'Additional Document Type updated successfully.');
    }

    public function destroy(AdditionalDocumentType $additionalDocumentType)
    {
        $additionalDocumentType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Additional Document Type deleted successfully.'
        ]);
    }
}
