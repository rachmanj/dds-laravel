<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Department;
use App\Imports\AdditionalDocumentImport;
use App\Exports\AdditionalDocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class AdditionalDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $documentTypes = AdditionalDocumentType::orderByName()->get();

        return view('additional_documents.index', compact('documentTypes'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $showAllRecords = $request->get('show_all_records', false);

        $query = AdditionalDocument::with(['type', 'creator']);

        // Apply search filters
        if ($request->filled('search_number')) {
            $query->where('document_number', 'like', '%' . $request->search_number . '%');
        }

        if ($request->filled('search_project')) {
            $query->where('project', 'like', '%' . $request->search_project . '%');
        }

        if ($request->filled('filter_type')) {
            $query->where('type_id', $request->filter_type);
        }

        if ($request->filled('filter_status')) {
            $query->where('status', $request->filter_status);
        }

        if ($request->filled('date_range')) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) == 2) {
                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        }

        // Apply location-based filtering for non-admin users
        if (!$user->hasRole(['admin', 'superadmin']) || !$showAllRecords) {
            $locationCode = $user->department_location_code;
            if ($locationCode) {
                $query->where('cur_loc', $locationCode);
            } else {
                // If user has no department, show documents with no location or 'DEFAULT' location
                $query->where(function ($q) {
                    $q->whereNull('cur_loc')
                        ->orWhere('cur_loc', 'DEFAULT');
                });
            }
        }

        $documents = $query->orderBy('created_at', 'desc')->get();

        return DataTables::of($documents)
            ->addIndexColumn()
            ->addColumn('actions', function ($document) use ($user) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<a href="' . route('additional-documents.show', $document) . '" class="btn btn-info btn-xs" title="View Document"><i class="fas fa-eye"></i></a>';

                if ($document->canBeEditedBy($user)) {
                    $actions .= '<a href="' . route('additional-documents.edit', $document) . '" class="btn btn-warning btn-xs" title="Edit Document"><i class="fas fa-edit"></i></a>';
                }

                if ($document->canBeDeletedBy($user)) {
                    $actions .= '<button type="button" class="btn btn-danger btn-xs delete-document" data-id="' . $document->id . '" data-number="' . $document->document_number . '" title="Delete Document"><i class="fas fa-trash"></i></button>';
                }

                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create()
    {
        $documentTypes = AdditionalDocumentType::orderByName()->get();
        $user = Auth::user();

        return view('additional_documents.create', compact('documentTypes', 'user'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type_id' => 'required|exists:additional_document_types,id',
            'document_number' => 'required|string|max:255',
            'document_date' => 'required|date',
            'po_no' => 'nullable|string|max:50',
            'receive_date' => 'required|date',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ]);

        $user = Auth::user();
        $data = $request->only([
            'type_id',
            'document_number',
            'document_date',
            'po_no',
            'receive_date',
            'remarks'
        ]);

        $data['created_by'] = $user->id;
        // Get user's department location code, fallback to 'DEFAULT' if not assigned
        $data['cur_loc'] = $user->department_location_code ?: 'DEFAULT';
        $data['status'] = 'open';

        // Handle file upload
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('attachments', $fileName, 'public');
            $data['attachment'] = $filePath;
        }

        AdditionalDocument::create($data);

        return redirect()->route('additional-documents.index')
            ->with('success', 'Additional Document created successfully.');
    }

    public function show(AdditionalDocument $additionalDocument)
    {
        $user = Auth::user();

        // Check if user can view this document
        if (!$user->hasRole(['admin', 'superadmin'])) {
            $userLocationCode = $user->department_location_code;
            if ($userLocationCode) {
                // User has department, check if document location matches
                if ($additionalDocument->cur_loc !== $userLocationCode) {
                    abort(403, 'You do not have permission to view this document.');
                }
            } else {
                // User has no department, only allow viewing documents with no location or 'DEFAULT' location
                if ($additionalDocument->cur_loc && $additionalDocument->cur_loc !== 'DEFAULT') {
                    abort(403, 'You do not have permission to view this document.');
                }
            }
        }

        $additionalDocument->load(['type', 'creator.department']);

        return view('additional_documents.show', compact('additionalDocument'));
    }

    public function edit(AdditionalDocument $additionalDocument)
    {
        $user = Auth::user();

        // Check if user can edit this document
        if (!$additionalDocument->canBeEditedBy($user)) {
            abort(403, 'You do not have permission to edit this document.');
        }

        $documentTypes = AdditionalDocumentType::orderByName()->get();
        $additionalDocument->load(['type', 'creator.department']);

        return view('additional_documents.edit', compact('additionalDocument', 'documentTypes'));
    }

    public function update(Request $request, AdditionalDocument $additionalDocument)
    {
        $user = Auth::user();

        // Check if user can edit this document
        if (!$additionalDocument->canBeEditedBy($user)) {
            abort(403, 'You do not have permission to edit this document.');
        }

        $request->validate([
            'type_id' => 'required|exists:additional_document_types,id',
            'document_number' => 'required|string|max:255',
            'document_date' => 'required|date',
            'po_no' => 'nullable|string|max:50',
            'receive_date' => 'required|date',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ]);

        $data = $request->only([
            'type_id',
            'document_number',
            'document_date',
            'po_no',
            'receive_date',
            'remarks'
        ]);

        // Handle file upload
        if ($request->hasFile('attachment')) {
            // Delete old file if exists
            if ($additionalDocument->attachment) {
                Storage::disk('public')->delete($additionalDocument->attachment);
            }

            $file = $request->file('attachment');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('attachments', $fileName, 'public');
            $data['attachment'] = $filePath;
        }

        $additionalDocument->update($data);

        return redirect()->route('additional-documents.index')
            ->with('success', 'Additional Document updated successfully.');
    }

    public function destroy(AdditionalDocument $additionalDocument)
    {
        $user = Auth::user();

        // Check if user can delete this document
        if (!$additionalDocument->canBeDeletedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this document.'
            ], 403);
        }

        // Delete attachment file if exists
        if ($additionalDocument->attachment) {
            Storage::disk('public')->delete($additionalDocument->attachment);
        }

        $additionalDocument->delete();

        return response()->json([
            'success' => true,
            'message' => 'Additional Document deleted successfully.'
        ]);
    }

    public function downloadAttachment(AdditionalDocument $additionalDocument)
    {
        $user = Auth::user();

        // Check if user can view this document
        if (!$user->hasRole(['admin', 'superadmin'])) {
            $userLocationCode = $user->department_location_code;
            if ($userLocationCode) {
                // User has department, check if document location matches
                if ($additionalDocument->cur_loc !== $userLocationCode) {
                    abort(403, 'You do not have permission to download this attachment.');
                }
            } else {
                // User has no department, only allow downloading documents with no location or 'DEFAULT' location
                if ($additionalDocument->cur_loc && $additionalDocument->cur_loc !== 'DEFAULT') {
                    abort(403, 'You do not have permission to download this attachment.');
                }
            }
        }

        if (!$additionalDocument->attachment) {
            abort(404, 'No attachment found for this document.');
        }

        $filePath = storage_path('app/public/' . $additionalDocument->attachment);

        if (!file_exists($filePath)) {
            abort(404, 'Attachment file not found.');
        }

        return response()->download($filePath);
    }

    public function import()
    {
        $documentTypes = AdditionalDocumentType::orderByName()->get();
        $user = Auth::user();

        return view('additional_documents.import', compact('documentTypes', 'user'));
    }

    public function processImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
            'document_type_id' => 'nullable|exists:additional_document_types,id',
        ]);

        try {
            $user = Auth::user();

            // Prepare import options
            $documentTypeId = $request->input('document_type_id');

            // Default values based on user's department
            $defaultValues = [
                'cur_loc' => $user->department_location_code ?: 'DEFAULT',
                'status' => 'open',
            ];

            // Create import instance
            $import = new AdditionalDocumentImport(
                $documentTypeId,
                $defaultValues
            );

            // Process the import
            Excel::import($import, $request->file('file'));

            // Get results
            $successCount = $import->getSuccessCount();
            $skippedCount = $import->getSkippedCount();
            $errors = $import->getErrors();

            // Prepare success message for Toastr
            $toastrMessage = "Import completed successfully!";
            if ($successCount > 0) {
                $toastrMessage .= " {$successCount} records imported.";
            }
            if ($skippedCount > 0) {
                $toastrMessage .= " {$skippedCount} records skipped.";
            }
            if (!empty($errors)) {
                $toastrMessage .= " " . count($errors) . " errors found.";
            }

            // Prepare summary data for the view
            $importSummary = [
                'success_count' => $successCount,
                'skipped_count' => $skippedCount,
                'error_count' => count($errors),
                'errors' => $errors,
                'total_processed' => $successCount + $skippedCount + count($errors),
                'file_name' => $request->file('file')->getClientOriginalName(),
                'imported_at' => now()->format('d/m/Y H:i:s'),
                'document_type' => $documentTypeId ? AdditionalDocumentType::find($documentTypeId)->type_name : 'Auto-detected',
                'duplicate_action' => 'skip',
                'check_duplicates' => true,
            ];

            return redirect()->route('additional-documents.import')
                ->with('import_success', $toastrMessage)
                ->with('import_summary', $importSummary);
        } catch (\Exception $e) {
            Log::error('Import error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new AdditionalDocumentTemplate(), 'additional_documents_template.xlsx');
    }
}
