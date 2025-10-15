<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Department;
use App\Imports\AdditionalDocumentImport;
use App\Imports\GeneralDocumentImport;
use App\Exports\AdditionalDocumentTemplate;
use App\Exports\GeneralDocumentTemplate;
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
        $vendorCodes = AdditionalDocument::whereNotNull('vendor_code')
            ->distinct()
            ->pluck('vendor_code')
            ->sort()
            ->values();
        $departments = \App\Models\Department::active()->orderBy('location_code')->get();

        return view('additional_documents.index', compact('documentTypes', 'vendorCodes', 'departments'));
    }

    public function data(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $showAllRecords = $request->get('show_all', false);

        $query = AdditionalDocument::with(['type', 'creator', 'invoices']);

        // Apply search filters
        if ($request->filled('search_number')) {
            $query->where('document_number', 'like', '%' . $request->search_number . '%');
        }

        if ($request->filled('search_po_no')) {
            $query->where('po_no', 'like', '%' . $request->search_po_no . '%');
        }

        if ($request->filled('search_vendor_code')) {
            $query->where('vendor_code', 'like', '%' . $request->search_vendor_code . '%');
        }

        if ($request->filled('search_content')) {
            $query->where(function ($q) use ($request) {
                $q->where('remarks', 'like', '%' . $request->search_content . '%')
                    ->orWhere('attachment', 'like', '%' . $request->search_content . '%');
            });
        }

        if ($request->filled('filter_type')) {
            $query->where('type_id', $request->filter_type);
        }

        if ($request->filled('filter_status')) {
            $query->where('status', $request->filter_status);
        }

        if ($request->filled('filter_vendor_code')) {
            $query->where('vendor_code', $request->filter_vendor_code);
        }

        if ($request->filled('filter_location')) {
            $query->where('cur_loc', $request->filter_location);
        }

        // Enhanced date range filtering
        if ($request->filled('date_range')) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) == 2) {
                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();

                $dateType = $request->get('date_type', 'created_at');
                $query->whereBetween($dateType, [$startDate, $endDate]);
            }
        }

        // Handle search presets
        if ($request->filled('search_preset')) {
            $preset = $request->search_preset;
            switch ($preset) {
                case 'recent':
                    $query->where('created_at', '>=', now()->subDays(30));
                    break;
                case 'open':
                    $query->where('status', 'open');
                    break;
                case 'my_department':
                    $locationCode = $user->department_location_code;
                    if ($locationCode) {
                        $query->where('cur_loc', $locationCode);
                    }
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('created_at', now()->subMonth()->month)
                        ->whereYear('created_at', now()->subMonth()->year);
                    break;
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

        // Get documents and sort by days in current location (oldest first - highest days first)
        // Calculate days difference in the query for better performance
        $documents = $query->get()->sortByDesc(function ($document) {
            $arrivalDate = $document->current_location_arrival_date;
            return $arrivalDate ? $arrivalDate->diffInDays(now()) : 0;
        })->values();

        return DataTables::of($documents)
            ->addIndexColumn()
            ->addColumn('days_difference', function ($document) {
                // Use department-specific aging calculation
                $daysInCurrentLocation = $document->days_in_current_location;

                if ($daysInCurrentLocation == 0) {
                    return '<span class="text-muted">-</span>';
                }

                // Round to 1 decimal place
                $roundedDays = round($daysInCurrentLocation, 1);

                if ($roundedDays <= 7) {
                    return '<span class="badge badge-success">' . $roundedDays . '</span>';
                } elseif ($roundedDays <= 14) {
                    return '<span class="badge badge-warning">' . $roundedDays . '</span>';
                } else {
                    return '<span class="badge badge-danger">' . $roundedDays . '</span>';
                }
            })
            ->addColumn('invoice_numbers', function ($document) {
                if ($document->invoices && $document->invoices->count() > 0) {
                    $invoiceNumbers = $document->invoices->pluck('invoice_number')->toArray();
                    return '<small class="text-muted">' . implode(', ', $invoiceNumbers) . '</small>';
                }
                return '<span class="text-muted">-</span>';
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
            ->rawColumns(['invoice_numbers', 'days_difference', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $documentTypes = AdditionalDocumentType::orderByName()->get();
        $projects = \App\Models\Project::active()->orderBy('code')->get();
        $departments = \App\Models\Department::active()->orderBy('location_code')->get();
        $user = Auth::user();

        return view('additional_documents.create', compact('documentTypes', 'projects', 'departments', 'user'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type_id' => 'required|exists:additional_document_types,id',
            'document_number' => 'required|string|max:255',
            'document_date' => 'required|date',
            'po_no' => 'nullable|string|max:50',
            'vendor_code' => 'nullable|string|max:50',
            'project' => 'nullable|string|max:50',
            'receive_date' => 'required|date',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:51200',
            'cur_loc' => 'nullable|string|max:50',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $data = $request->only([
            'type_id',
            'document_number',
            'document_date',
            'po_no',
            'vendor_code',
            'project',
            'receive_date',
            'remarks'
        ]);

        $data['created_by'] = $user->id;

        // Handle location based on user role
        if ($user->hasAnyRole(['superadmin', 'admin', 'accounting']) && $request->filled('cur_loc')) {
            // Privileged users can select any location
            $data['cur_loc'] = $request->cur_loc;
        } else {
            // Regular users get their department location
            $data['cur_loc'] = $user->department_location_code ?: 'DEFAULT';
        }

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
        $departments = \App\Models\Department::active()->orderBy('location_code')->get();
        $additionalDocument->load(['type', 'creator.department']);

        return view('additional_documents.edit', compact('additionalDocument', 'documentTypes', 'projects', 'departments'));
    }

    public function update(Request $request, AdditionalDocument $additionalDocument)
    {
        $user = Auth::user();

        // Check if user can edit this document
        if (!$additionalDocument->canBeEditedBy($user)) {
            abort(403, 'You do not have permission to edit this document.');
        }

        // Check if location change is being attempted
        if ($request->has('cur_loc') && $request->cur_loc !== $additionalDocument->cur_loc) {
            if (!$additionalDocument->canChangeLocationManually()) {
                return redirect()->back()
                    ->withErrors([
                        'cur_loc' => 'Cannot change location manually. This document has distribution history. Location can only be changed through the distribution process.'
                    ])
                    ->withInput();
            }
        }

        $request->validate([
            'type_id' => 'required|exists:additional_document_types,id',
            'document_number' => 'required|string|max:255',
            'document_date' => 'required|date',
            'po_no' => 'nullable|string|max:50',
            'vendor_code' => 'nullable|string|max:50',
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
            'vendor_code',
            'project',
            'receive_date',
            'cur_loc',
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

    public function previewAttachment(AdditionalDocument $additionalDocument)
    {
        $user = Auth::user();

        // Check if user can view this document
        if (!array_intersect($user->roles->pluck('name')->toArray(), ['admin', 'superadmin'])) {
            $userLocationCode = $user->department_location_code;
            if ($userLocationCode) {
                // User has department, check if document location matches
                if ($additionalDocument->cur_loc !== $userLocationCode) {
                    abort(403, 'You do not have permission to preview this attachment.');
                }
            } else {
                // User has no department, only allow previewing documents with no location or 'DEFAULT' location
                if ($additionalDocument->cur_loc && $additionalDocument->cur_loc !== 'DEFAULT') {
                    abort(403, 'You do not have permission to preview this attachment.');
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

        $mimeType = mime_content_type($filePath);
        $fileName = basename($additionalDocument->attachment);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"'
        ]);
    }

    public function import()
    {
        $this->authorize('import-additional-documents');

        $documentTypes = AdditionalDocumentType::orderByName()->get();
        $user = Auth::user();

        return view('additional_documents.import', compact('documentTypes', 'user'));
    }

    public function importGeneral()
    {
        $this->authorize('import-general-documents');

        $user = Auth::user();

        return view('additional_documents.import-general', compact('user'));
    }

    public function processImport(Request $request)
    {
        $this->authorize('import-additional-documents');

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
        return Excel::download(new AdditionalDocumentTemplate(), 'ito_documents_template.xlsx');
    }

    public function processGeneralImport(Request $request)
    {
        $this->authorize('import-general-documents');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:51200', // 50MB max
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

            // Default values based on user's department
            $defaultValues = [
                'cur_loc' => $user->department_location_code ?: 'DEFAULT',
                'status' => 'open',
            ];

            // Log import attempt for debugging
            Log::info('Starting General Excel import:', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'default_values' => $defaultValues
            ]);

            // Create import instance
            $import = new GeneralDocumentImport($defaultValues);

            // Process the import
            Excel::import($import, $file);

            // Get results
            $successCount = $import->getSuccessCount();
            $skippedCount = $import->getSkippedCount();
            $errors = $import->getErrors();
            $documentTypeCounts = $import->getDocumentTypeCounts();

            // Prepare success message for Toastr
            $toastrMessage = "General documents import completed successfully!";
            if ($successCount > 0) {
                $toastrMessage .= " {$successCount} documents imported.";
            }
            if ($skippedCount > 0) {
                $toastrMessage .= " {$skippedCount} rows skipped.";
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
                'document_type' => 'General Documents (DO/GR/MR)',
                'duplicate_action' => 'skip',
                'check_duplicates' => true,
                'document_type_counts' => $documentTypeCounts,
            ];

            return redirect()->route('additional-documents.import-general')
                ->with('general_import_success', $toastrMessage)
                ->with('general_import_summary', $importSummary);
        } catch (\Exception $e) {
            Log::error('General import error: ' . $e->getMessage());
            Log::error('General import error trace: ' . $e->getTraceAsString());

            // Provide more specific error messages for common issues
            $errorMessage = 'General import failed: ' . $e->getMessage();

            if (str_contains($e->getMessage(), 'Column count doesn\'t match value count')) {
                $errorMessage = 'General import failed: Excel column structure mismatch. Please use the provided general template format.';
            } elseif (str_contains($e->getMessage(), 'SQLSTATE[21S01]')) {
                $errorMessage = 'General import failed: Database column mismatch. Please check the Excel template format.';
            }

            return redirect()->route('additional-documents.import-general')
                ->with('general_error', $errorMessage)
                ->withInput();
        }
    }

    public function downloadGeneralTemplate()
    {
        $this->authorize('import-general-documents');

        return Excel::download(new GeneralDocumentTemplate(), 'general_documents_template.xlsx');
    }

    /**
     * Create additional document on-the-fly from invoice forms
     */
    public function createOnTheFly(Request $request)
    {
        // Check permission using the specific permission
        /** @var \App\Models\User $user */
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

    // ENHANCED SEARCH & FILTERING METHODS

    /**
     * Export additional documents with current search filters
     */
    public function export(Request $request)
    {
        try {
            $query = AdditionalDocument::with(['type', 'creator', 'distributions.originDepartment', 'distributions.destinationDepartment']);

            // Apply the same filters as the data method
            $this->applySearchFilters($query, $request);

            // Sort by days in current location (oldest first - highest days first) for consistency with main table
            $documents = $query->get()->sortByDesc(function ($document) {
                $arrivalDate = $document->current_location_arrival_date;
                return $arrivalDate ? $arrivalDate->diffInDays(now()) : 0;
            })->values();

            // Transform data for export
            $exportData = $documents->map(function ($document) {
                return [
                    'Document Number' => $document->document_number,
                    'Document Type' => $document->type->type_name ?? '',
                    'Document Date' => $document->document_date ? \Carbon\Carbon::parse($document->document_date)->format('d/m/Y') : '',
                    'PO Number' => $document->po_no ?? '',
                    'Vendor Code' => $document->vendor_code ?? '',
                    'Receive Date' => $document->receive_date ? \Carbon\Carbon::parse($document->receive_date)->format('d/m/Y') : '',
                    'Current Location' => $document->cur_loc ?? '',
                    'Status' => $document->status ?? '',
                    'Distribution Status' => $document->distribution_status ?? '',
                    'Remarks' => $document->remarks ?? '',
                    'Created By' => $document->creator->name ?? '',
                    'Created At' => $document->created_at ? $document->created_at->format('d/m/Y H:i') : '',
                ];
            });

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\AdditionalDocumentExport($exportData),
                'additional_documents_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
            );
        } catch (\Exception $e) {
            Log::error('Additional documents export failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Get search presets for current user
     */
    public function searchPresetsIndex()
    {
        try {
            $user = Auth::user();
            $presets = \App\Models\SearchPreset::where('user_id', $user->id)
                ->where('model_type', 'additional_documents')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $presets
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load search presets'
            ], 500);
        }
    }

    /**
     * Store a new search preset
     */
    public function searchPresetsStore(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'filters' => 'required|string'
            ]);

            $user = Auth::user();

            $preset = \App\Models\SearchPreset::create([
                'user_id' => $user->id,
                'model_type' => 'additional_documents',
                'name' => $request->name,
                'filters' => $request->filters,
            ]);

            return response()->json([
                'success' => true,
                'data' => $preset,
                'message' => 'Search preset saved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save search preset'
            ], 500);
        }
    }

    /**
     * Get a specific search preset
     */
    public function searchPresetsShow($id)
    {
        try {
            $user = Auth::user();
            $preset = \App\Models\SearchPreset::where('id', $id)
                ->where('user_id', $user->id)
                ->where('model_type', 'additional_documents')
                ->first();

            if (!$preset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search preset not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $preset
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load search preset'
            ], 500);
        }
    }

    /**
     * Delete a search preset
     */
    public function searchPresetsDestroy($id)
    {
        try {
            $user = Auth::user();
            $preset = \App\Models\SearchPreset::where('id', $id)
                ->where('user_id', $user->id)
                ->where('model_type', 'additional_documents')
                ->first();

            if (!$preset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search preset not found'
                ], 404);
            }

            $preset->delete();

            return response()->json([
                'success' => true,
                'message' => 'Search preset deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete search preset'
            ], 500);
        }
    }

    /**
     * Apply search filters to query (extracted from data method for reuse)
     */
    private function applySearchFilters($query, $request)
    {
        // Apply search filters
        if ($request->filled('search_number')) {
            $query->where('document_number', 'like', '%' . $request->search_number . '%');
        }

        if ($request->filled('search_po_no')) {
            $query->where('po_no', 'like', '%' . $request->search_po_no . '%');
        }

        if ($request->filled('search_vendor_code')) {
            $query->where('vendor_code', 'like', '%' . $request->search_vendor_code . '%');
        }

        if ($request->filled('search_project')) {
            $query->where('project', 'like', '%' . $request->search_project . '%');
        }

        if ($request->filled('search_remarks')) {
            $query->where('remarks', 'like', '%' . $request->search_remarks . '%');
        }

        if ($request->filled('filter_type')) {
            $query->where('type_id', $request->filter_type);
        }

        if ($request->filled('filter_status')) {
            $query->where('status', $request->filter_status);
        }

        if ($request->filled('filter_vendor_code')) {
            $query->where('vendor_code', $request->filter_vendor_code);
        }

        if ($request->filled('filter_location')) {
            $query->where('cur_loc', $request->filter_location);
        }

        if ($request->filled('date_range')) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) == 2) {
                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();
                $query->whereBetween('document_date', [$startDate, $endDate]);
            }
        }

        // Show all records permission check
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($request->filled('show_all_records') && $request->show_all_records === 'on') {
            if (!$user->can('see-all-record-switch')) {
                // Fallback to user's department only
                $query->where('cur_loc', $user->department_location_code);
            }
        } else {
            // Default: show only user's department records
            $query->where('cur_loc', $user->department_location_code);
        }
    }
}
