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
        $projects = \App\Models\Project::active()->orderBy('code')->get();

        return view('additional_documents.index', compact('documentTypes', 'projects'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $showAllRecords = $request->get('show_all', false);

        $query = AdditionalDocument::with(['type', 'creator']);

        // Apply search filters
        if ($request->filled('search_number')) {
            $query->where('document_number', 'like', '%' . $request->search_number . '%');
        }

        if ($request->filled('search_po_no')) {
            $query->where('po_no', 'like', '%' . $request->search_po_no . '%');
        }

        if ($request->filled('filter_type')) {
            $query->where('type_id', $request->filter_type);
        }

        if ($request->filled('filter_status')) {
            $query->where('status', $request->filter_status);
        }

        if ($request->filled('filter_project')) {
            $query->where('project', $request->filter_project);
        }

        if ($request->filled('date_range')) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) == 2) {
                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        }

        // Apply location-based filtering unless user is admin/superadmin and show_all is requested
        if (!$showAllRecords && !array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode) {
                $query->where('cur_loc', $locationCode);
            }
        }

        // Apply distribution status filtering for non-admin users when show_all is false
        if (!$showAllRecords && !array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin'])) {
            $query->whereIn('distribution_status', ['available', 'distributed']);
        }

        // Show all records for users with see-all-record-switch permission if requested
        if ($showAllRecords && $user->can('see-all-record-switch')) {
            // Don't apply additional filters - show all documents
        }

        $documents = $query->orderBy('created_at', 'desc')->get();

        return DataTables::of($documents)
            ->addIndexColumn()
            ->addColumn('days_difference', function ($document) {
                if (!$document->receive_date) {
                    return '<span class="text-muted">-</span>';
                }
                $now = \Carbon\Carbon::now()->startOfDay();
                $receiveDate = \Carbon\Carbon::parse($document->receive_date)->startOfDay();
                $days = $now->timestamp - $receiveDate->timestamp;
                $days = $days / (24 * 60 * 60); // Convert seconds to days
                $roundedDays = round($days); // Round to nearest integer

                if ($roundedDays < 0) {
                    $roundedDays = abs($roundedDays);
                    return '<span class="badge badge-info">' . $roundedDays . '</span>';
                } elseif ($roundedDays < 7) {
                    return '<span class="badge badge-success">' . $roundedDays . '</span>';
                } elseif ($roundedDays == 7) {
                    return '<span class="badge badge-warning">' . $roundedDays . '</span>';
                } else {
                    return '<span class="badge badge-danger">' . $roundedDays . '</span>';
                }
            })
            ->addColumn('actions', function ($document) use ($user) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<button type="button" class="btn btn-info btn-xs show-document" data-id="' . $document->id . '" title="View Document"><i class="fas fa-eye"></i></button>';

                if ($document->canBeEditedBy($user)) {
                    $actions .= '<a href="' . route('additional-documents.edit', $document) . '" class="btn btn-warning btn-xs" title="Edit Document"><i class="fas fa-edit"></i></a>';
                }

                if ($document->canBeDeletedBy($user)) {
                    $actions .= '<button type="button" class="btn btn-danger btn-xs delete-document" data-id="' . $document->id . '" data-number="' . $document->document_number . '" title="Delete Document"><i class="fas fa-trash"></i></button>';
                }

                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['days_difference', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $documentTypes = AdditionalDocumentType::orderByName()->get();
        $projects = \App\Models\Project::active()->orderBy('code')->get();
        $user = Auth::user();

        return view('additional_documents.create', compact('documentTypes', 'projects', 'user'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type_id' => 'required|exists:additional_document_types,id',
            'document_number' => 'required|string|max:255',
            'document_date' => 'required|date',
            'po_no' => 'nullable|string|max:50',
            'project' => 'nullable|string|max:50',
            'receive_date' => 'required|date',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:51200',
        ]);

        $user = Auth::user();
        $data = $request->only([
            'type_id',
            'document_number',
            'document_date',
            'po_no',
            'project',
            'receive_date',
            'remarks'
        ]);

        $data['created_by'] = $user->id;
        // Get user's department location code, fallback to 'DEFAULT' if not assigned
        $data['cur_loc'] = $user->department_location_code ?: 'DEFAULT';
        // Set default project to user's department project if not provided
        if (empty($data['project']) && $user->project) {
            $data['project'] = $user->project;
        }
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
        if (!array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin'])) {
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

        $additionalDocument->load(['type', 'creator.department', 'distributions']);

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
        $projects = \App\Models\Project::active()->orderBy('code')->get();
        $additionalDocument->load(['type', 'creator.department']);

        return view('additional_documents.edit', compact('additionalDocument', 'documentTypes', 'projects'));
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
            'project' => 'nullable|string|max:50',
            'receive_date' => 'required|date',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:51200',
        ]);

        $data = $request->only([
            'type_id',
            'document_number',
            'document_date',
            'po_no',
            'project',
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
        if (!array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin'])) {
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
            'file' => 'required|file|mimes:xlsx,xls|max:51200', // 50MB max
            'document_type_id' => 'nullable|exists:additional_document_types,id',
        ]);

        try {
            $user = Auth::user();

            // Validate file before processing
            $file = $request->file('file');
            if (!$file->isValid()) {
                throw new \Exception('Invalid file uploaded');
            }

            // Check file size
            if ($file->getSize() > 50 * 1024 * 1024) { // 50MB
                throw new \Exception('File size exceeds 50MB limit');
            }

            // Validate Excel file format
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, ['xlsx', 'xls'])) {
                throw new \Exception('Invalid file format. Only .xlsx and .xls files are supported.');
            }

            // Try to read a small portion of the file to validate it's a valid Excel file
            try {
                $testData = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                    public function array(array $array)
                    {
                        return $array;
                    }
                }, $file);

                if (empty($testData) || empty($testData[0])) {
                    throw new \Exception('Excel file appears to be empty or cannot be read');
                }
            } catch (\Exception $e) {
                throw new \Exception('Invalid Excel file format or corrupted file: ' . $e->getMessage());
            }

            // Prepare import options
            $documentTypeId = $request->input('document_type_id');

            // Default values based on user's department
            $defaultValues = [
                'cur_loc' => $user->department_location_code ?: 'DEFAULT',
                'status' => 'open',
            ];

            // Log import attempt for debugging
            Log::info('Starting Excel import:', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'document_type_id' => $documentTypeId,
                'default_values' => $defaultValues
            ]);

            // Create import instance
            $import = new AdditionalDocumentImport(
                $documentTypeId,
                $defaultValues
            );

            // Process the import
            Excel::import($import, $file);

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
            Log::error('Import error trace: ' . $e->getTraceAsString());

            // Provide more specific error messages for common issues
            $errorMessage = 'Import failed: ' . $e->getMessage();

            if (str_contains($e->getMessage(), 'Column count doesn\'t match value count')) {
                $errorMessage = 'Import failed: Excel column structure mismatch. Please use the provided template format.';
            } elseif (str_contains($e->getMessage(), 'SQLSTATE[21S01]')) {
                $errorMessage = 'Import failed: Database column mismatch. Please check the Excel template format.';
            }

            return redirect()->back()
                ->with('error', $errorMessage)
                ->withInput();
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new AdditionalDocumentTemplate(), 'additional_documents_template.xlsx');
    }

    /**
     * Create additional document on-the-fly from invoice forms
     */
    public function createOnTheFly(Request $request)
    {
        // Check permission using the specific permission
        $user = Auth::user();
        if (!$user->can('on-the-fly-addoc-feature')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create additional documents on-the-fly.'
            ], 403);
        }

        // Validate request
        $request->validate([
            'document_type_id' => 'required|exists:additional_document_types,id',
            'document_number' => 'required|string|max:255',
            'document_date' => 'nullable|date',
            'document_receive_date' => 'nullable|date',
            'cur_loc' => 'required|string|max:50',
            'po_no' => 'nullable|string|max:255',
            'project' => 'nullable|string|max:50',
        ]);

        try {

            // Create the additional document
            $additionalDocument = AdditionalDocument::create([
                'document_number' => $request->document_number,
                'document_date' => $request->document_date,
                'document_receive_date' => $request->document_receive_date,
                'type_id' => $request->document_type_id,
                'cur_loc' => $request->cur_loc,
                'po_no' => $request->po_no,
                'project' => $request->project,
                'status' => 'open',
                'distribution_status' => 'available',
                'created_by' => $user->id,
                'origin_wh' => $request->cur_loc,
                'destinatic' => $request->cur_loc,
            ]);

            // Load relationships for response
            $additionalDocument->load(['type', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Additional document created successfully and will be automatically attached to the invoice.',
                'document' => [
                    'id' => $additionalDocument->id,
                    'document_number' => $additionalDocument->document_number,
                    'document_type' => $additionalDocument->type->type_name,
                    'document_date' => $additionalDocument->document_date ? \Carbon\Carbon::parse($additionalDocument->document_date)->format('d/m/Y') : null,
                    'po_no' => $additionalDocument->po_no,
                    'cur_loc' => $additionalDocument->cur_loc,
                    'status' => $additionalDocument->status,
                    'distribution_status' => $additionalDocument->distribution_status,
                    'is_in_user_department' => $additionalDocument->cur_loc === $user->department_location_code,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('On-the-fly additional document creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create additional document: ' . $e->getMessage()
            ], 500);
        }
    }
}
