<?php

namespace App\Http\Controllers;

use App\Exports\AdditionalDocumentTemplate;
use App\Exports\GeneralDocumentTemplate;
use App\Imports\AdditionalDocumentImport;
use App\Imports\GeneralDocumentImport;
use App\Jobs\SyncSapItoDocumentsJob;
use App\Jobs\VerifyDocumentSignatureJob;
use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Department;
use App\Models\Project;
use App\Models\SignatureMatchResult;
use App\Models\SignatureSpecimen;
use App\Support\AdditionalDocumentListFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

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

    private function additionalDocumentsFilteredQuery(Request $request, \App\Models\User $user, bool $showAllRecords)
    {
        $query = AdditionalDocumentListFilters::baseQuery();
        AdditionalDocumentListFilters::apply($request, $query, $user, true, $showAllRecords);

        return $query;
    }

    public function data(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $showAllRecords = (bool) $request->get('show_all', false);

        $query = $this->additionalDocumentsFilteredQuery($request, $user, $showAllRecords);

        // Use DataTables with database-level sorting and pagination
        return DataTables::of($query)
            ->addIndexColumn()
            ->orderColumn('days_difference', 'days_in_location $1')
            ->addColumn('days_difference', function ($document) {
                // Use pre-calculated days_in_location from query
                $daysInCurrentLocation = $document->days_in_location ?? $document->days_in_current_location;

                if ($daysInCurrentLocation == 0 || $daysInCurrentLocation === null) {
                    return '<span class="text-muted">-</span>';
                }

                // Round to 1 decimal place
                $roundedDays = round($daysInCurrentLocation, 1);

                if ($roundedDays <= 7) {
                    return '<span class="badge badge-success">'.$roundedDays.'</span>';
                } elseif ($roundedDays <= 14) {
                    return '<span class="badge badge-warning">'.$roundedDays.'</span>';
                } else {
                    return '<span class="badge badge-danger">'.$roundedDays.'</span>';
                }
            })
            ->addColumn('invoice_numbers', function ($document) {
                if ($document->invoices && $document->invoices->count() > 0) {
                    $invoiceNumbers = $document->invoices->pluck('invoice_number')->toArray();

                    return '<small class="text-muted">'.implode(', ', $invoiceNumbers).'</small>';
                }

                return '<span class="text-muted">-</span>';
            })
            ->addColumn('actions', function ($document) use ($user) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<button type="button" class="btn btn-info btn-xs show-document" data-id="'.$document->id.'" title="View Document"><i class="fas fa-eye"></i></button>';

                if ($document->canBeEditedBy($user)) {
                    $actions .= '<a href="'.route('additional-documents.edit', $document).'" class="btn btn-warning btn-xs" title="Edit Document"><i class="fas fa-edit"></i></a>';
                }

                if ($document->canBeDeletedBy($user)) {
                    $actions .= '<button type="button" class="btn btn-danger btn-xs delete-document" data-id="'.$document->id.'" data-number="'.$document->document_number.'" title="Delete Document"><i class="fas fa-trash"></i></button>';
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
            'remarks',
        ]);

        $data['created_by'] = $user->id;

        // Handle location based on user role
        if ($user->hasAnyRole(['superadmin', 'admin', 'accounting', 'finance']) && $request->filled('cur_loc')) {
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
            $fileName = time().'_'.$file->getClientOriginalName();
            $filePath = $file->storeAs('attachments', $fileName, 'public');
            $data['attachment'] = $filePath;
        }

        $document = AdditionalDocument::create($data);

        $this->dispatchSignatureVerificationIfNeeded($document, $data['project'] ?? null);

        $redirect = redirect()->route('additional-documents.index')
            ->with('success', 'Additional Document created successfully.');

        $duplicate = $this->findDuplicateAdditionalDocument(
            (int) $data['type_id'],
            $data['document_number'],
            $data['vendor_code'] ?? null,
            $document->id
        );

        if ($duplicate) {
            $redirect->with('warning', 'Nomor dokumen sudah ada pada dokumen #'.$duplicate->id.' — periksa kemungkinan duplikat.');
        }

        return $redirect;
    }

    public function show(AdditionalDocument $additionalDocument)
    {
        $user = Auth::user();

        // Check if user can view this document
        if (! $user->hasAnyRole(['admin', 'superadmin', 'accounting', 'finance'])) {
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

        $additionalDocument->load(['type', 'creator.department', 'distributions', 'signatureProject', 'signatureCheckedBy', 'signatureOverrideBy']);

        $projects = Project::active()->orderBy('code')->get();

        return view('additional_documents.show', compact('additionalDocument', 'projects'));
    }

    public function edit(AdditionalDocument $additionalDocument)
    {
        $user = Auth::user();

        // Check if user can edit this document
        if (! $additionalDocument->canBeEditedBy($user)) {
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
        if (! $additionalDocument->canBeEditedBy($user)) {
            abort(403, 'You do not have permission to edit this document.');
        }

        // Check if location change is being attempted
        if ($request->has('cur_loc') && $request->cur_loc !== $additionalDocument->cur_loc) {
            if (! $additionalDocument->canChangeLocationManually()) {
                return redirect()->back()
                    ->withErrors([
                        'cur_loc' => 'Cannot change location manually. This document has distribution history. Location can only be changed through the distribution process.',
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
            'remarks',
        ]);

        // Handle file upload
        if ($request->hasFile('attachment')) {
            // Delete old file if exists
            if ($additionalDocument->attachment) {
                Storage::disk('public')->delete($additionalDocument->attachment);
            }

            $file = $request->file('attachment');
            $fileName = time().'_'.$file->getClientOriginalName();
            $filePath = $file->storeAs('attachments', $fileName, 'public');
            $data['attachment'] = $filePath;
        }

        $additionalDocument->update($data);

        if ($request->hasFile('attachment')) {
            $additionalDocument->refresh();
            $this->dispatchSignatureVerificationIfNeeded($additionalDocument, $additionalDocument->project);
        }

        $redirect = redirect()->route('additional-documents.index')
            ->with('success', 'Additional Document updated successfully.');

        $duplicate = $this->findDuplicateAdditionalDocument(
            (int) $data['type_id'],
            $data['document_number'],
            $data['vendor_code'] ?? null,
            $additionalDocument->id
        );

        if ($duplicate) {
            $redirect->with('warning', 'Nomor dokumen sudah ada pada dokumen #'.$duplicate->id.' — periksa kemungkinan duplikat.');
        }

        return $redirect;
    }

    public function destroy(AdditionalDocument $additionalDocument)
    {
        $user = Auth::user();

        // Check if user can delete this document
        if (! $additionalDocument->canBeDeletedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this document.',
            ], 403);
        }

        // Delete attachment file if exists
        if ($additionalDocument->attachment) {
            Storage::disk('public')->delete($additionalDocument->attachment);
        }

        $additionalDocument->delete();

        return response()->json([
            'success' => true,
            'message' => 'Additional Document deleted successfully.',
        ]);
    }

    public function downloadAttachment(AdditionalDocument $additionalDocument)
    {
        $user = Auth::user();

        // Check if user can view this document
        if (! $user->hasAnyRole(['admin', 'superadmin', 'accounting', 'finance'])) {
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

        if (! $additionalDocument->attachment) {
            abort(404, 'No attachment found for this document.');
        }

        $filePath = storage_path('app/public/'.$additionalDocument->attachment);

        if (! file_exists($filePath)) {
            abort(404, 'Attachment file not found.');
        }

        return response()->download($filePath);
    }

    public function previewAttachment(AdditionalDocument $additionalDocument)
    {
        $user = Auth::user();

        // Check if user can view this document
        if (! $user->hasAnyRole(['admin', 'superadmin', 'accounting', 'finance'])) {
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

        if (! $additionalDocument->attachment) {
            abort(404, 'No attachment found for this document.');
        }

        $filePath = storage_path('app/public/'.$additionalDocument->attachment);

        if (! file_exists($filePath)) {
            abort(404, 'Attachment file not found.');
        }

        $mimeType = mime_content_type($filePath);
        $fileName = basename($additionalDocument->attachment);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
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
            if (! $file->isValid()) {
                throw new \Exception('Invalid file uploaded');
            }

            // Check file size
            if ($file->getSize() > 50 * 1024 * 1024) { // 50MB
                throw new \Exception('File size exceeds 50MB limit');
            }

            // Validate Excel file format
            $extension = strtolower($file->getClientOriginalExtension());
            if (! in_array($extension, ['xlsx', 'xls'])) {
                throw new \Exception('Invalid file format. Only .xlsx and .xls files are supported.');
            }

            // Try to read a small portion of the file to validate it's a valid Excel file
            try {
                $testData = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray
                {
                    public function array(array $array)
                    {
                        return $array;
                    }
                }, $file);

                if (empty($testData) || empty($testData[0])) {
                    throw new \Exception('Excel file appears to be empty or cannot be read');
                }
            } catch (\Exception $e) {
                throw new \Exception('Invalid Excel file format or corrupted file: '.$e->getMessage());
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
                'default_values' => $defaultValues,
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
            $toastrMessage = 'Import completed successfully!';
            if ($successCount > 0) {
                $toastrMessage .= " {$successCount} records imported.";
            }
            if ($skippedCount > 0) {
                $toastrMessage .= " {$skippedCount} records skipped.";
            }
            if (! empty($errors)) {
                $toastrMessage .= ' '.count($errors).' errors found.';
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
            Log::error('Import error: '.$e->getMessage());
            Log::error('Import error trace: '.$e->getTraceAsString());

            // Provide more specific error messages for common issues
            $errorMessage = 'Import failed: '.$e->getMessage();

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
        return Excel::download(new AdditionalDocumentTemplate, 'ito_documents_template.xlsx');
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
            if (! $file->isValid()) {
                throw new \Exception('Invalid file uploaded');
            }

            // Check file size
            if ($file->getSize() > 50 * 1024 * 1024) { // 50MB
                throw new \Exception('File size exceeds 50MB limit');
            }

            // Validate Excel file format
            $extension = strtolower($file->getClientOriginalExtension());
            if (! in_array($extension, ['xlsx', 'xls'])) {
                throw new \Exception('Invalid file format. Only .xlsx and .xls files are supported.');
            }

            // Try to read a small portion of the file to validate it's a valid Excel file
            try {
                $testData = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray
                {
                    public function array(array $array)
                    {
                        return $array;
                    }
                }, $file);

                if (empty($testData) || empty($testData[0])) {
                    throw new \Exception('Excel file appears to be empty or cannot be read');
                }
            } catch (\Exception $e) {
                throw new \Exception('Invalid Excel file format or corrupted file: '.$e->getMessage());
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
                'default_values' => $defaultValues,
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
            $toastrMessage = 'General documents import completed successfully!';
            if ($successCount > 0) {
                $toastrMessage .= " {$successCount} documents imported.";
            }
            if ($skippedCount > 0) {
                $toastrMessage .= " {$skippedCount} rows skipped.";
            }
            if (! empty($errors)) {
                $toastrMessage .= ' '.count($errors).' errors found.';
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
            Log::error('General import error: '.$e->getMessage());
            Log::error('General import error trace: '.$e->getTraceAsString());

            // Provide more specific error messages for common issues
            $errorMessage = 'General import failed: '.$e->getMessage();

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

        return Excel::download(new GeneralDocumentTemplate, 'general_documents_template.xlsx');
    }

    /**
     * Create additional document on-the-fly from invoice forms
     */
    public function createOnTheFly(Request $request)
    {
        // Check permission using the specific permission
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->can('on-the-fly-addoc-feature')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create additional documents on-the-fly.',
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
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('On-the-fly additional document creation failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create additional document: '.$e->getMessage(),
            ], 500);
        }
    }

    public function checkDuplicateNumber(Request $request)
    {
        $typeId = $request->input('type_id');
        $documentNumber = $request->input('document_number');
        $vendorCode = $request->input('vendor_code');
        $excludeId = $request->input('exclude_id');

        $existing = $this->findDuplicateAdditionalDocument(
            $typeId !== null && $typeId !== '' ? (int) $typeId : null,
            is_string($documentNumber) ? $documentNumber : null,
            is_string($vendorCode) ? $vendorCode : null,
            $excludeId !== null && $excludeId !== '' ? (int) $excludeId : null
        );

        if ($existing) {
            return response()->json([
                'exists' => true,
                'document' => [
                    'id' => $existing->id,
                    'document_number' => $existing->document_number,
                    'po_no' => $existing->po_no,
                    'vendor_code' => $existing->vendor_code,
                    'document_date' => $existing->document_date
                        ? $existing->document_date->format('d/m/Y')
                        : null,
                ],
            ]);
        }

        return response()->json([
            'exists' => false,
            'document' => null,
        ]);
    }

    // ENHANCED SEARCH & FILTERING METHODS

    /**
     * Export additional documents with current search filters
     */
    public function export(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $showAllRecords = (bool) $request->get('show_all', false);

            $query = $this->additionalDocumentsFilteredQuery($request, $user, $showAllRecords);

            $documents = $query->get()->sortByDesc(function ($document) {
                return (float) ($document->days_in_location ?? 0);
            })->values();

            $exportData = $documents->map(function ($document) {
                return [
                    'Document Number' => $document->document_number,
                    'Document Type' => $document->type->type_name ?? '',
                    'Document Date' => $document->document_date ? \Carbon\Carbon::parse($document->document_date)->format('d/m/Y') : '',
                    'PO Number' => $document->po_no ?? '',
                    'Vendor Code' => $document->vendor_code ?? '',
                    'Receive Date' => $document->receive_date ? \Carbon\Carbon::parse($document->receive_date)->format('d/m/Y') : '',
                    'Current Location' => $document->cur_loc ?? '',
                    'Days in location' => round((float) ($document->days_in_location ?? 0), 1),
                    'Status' => $document->status ?? '',
                    'Distribution Status' => $document->distribution_status ?? '',
                    'Remarks' => $document->remarks ?? '',
                    'Created By' => $document->creator->name ?? '',
                    'Created At' => $document->created_at ? $document->created_at->format('d/m/Y H:i') : '',
                ];
            });

            return Excel::download(
                new \App\Exports\AdditionalDocumentExport($exportData),
                'additional_documents_'.now()->format('Y-m-d_H-i-s').'.xlsx'
            );
        } catch (\Exception $e) {
            Log::error('Additional documents export failed: '.$e->getMessage());

            return redirect()->back()->with('error', 'Export failed: '.$e->getMessage());
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
                'data' => $presets,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load search presets',
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
                'filters' => 'required|string',
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
                'message' => 'Search preset saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save search preset',
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

            if (! $preset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search preset not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $preset,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load search preset',
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

            if (! $preset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search preset not found',
                ], 404);
            }

            $preset->delete();

            return response()->json([
                'success' => true,
                'message' => 'Search preset deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete search preset',
            ], 500);
        }
    }

    public function sapSyncItoForm()
    {
        $todayDate = now()->toDateString();
        $yesterdayDate = now()->copy()->subDay()->toDateString();

        $itoSyncLogsToday = DB::table('sap_logs')
            ->where('action', 'query_sync')
            ->whereDate('created_at', $todayDate)
            ->orderByDesc('created_at')
            ->get();

        $itoSyncLogsYesterday = DB::table('sap_logs')
            ->where('action', 'query_sync')
            ->whereDate('created_at', $yesterdayDate)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.sap-sync-ito', compact(
            'itoSyncLogsToday',
            'itoSyncLogsYesterday',
            'todayDate',
            'yesterdayDate'
        ));
    }

    public function sapSyncIto(Request $request)
    {
        $request->validate([
            'date_range' => 'required|in:today,yesterday,custom',
            'start_date' => 'required_if:date_range,custom|date',
            'end_date' => 'required_if:date_range,custom|date|after_or_equal:start_date',
        ]);

        // Determine dates based on date_range selection
        $startDate = null;
        $endDate = null;

        if ($request->date_range === 'today') {
            $startDate = now()->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
        } elseif ($request->date_range === 'yesterday') {
            $startDate = now()->subDay()->format('Y-m-d');
            $endDate = now()->subDay()->format('Y-m-d');
        } else {
            // custom
            $startDate = $request->start_date;
            $endDate = $request->end_date;
        }

        try {
            // Run job synchronously to get immediate results
            $sapService = app(\App\Services\SapService::class);
            $job = new SyncSapItoDocumentsJob($startDate, $endDate, [
                'trigger' => 'web',
                'triggered_by_user_id' => Auth::id() ?? 1,
            ]);

            $job->handle($sapService);

            $logEntry = DB::table('sap_logs')
                ->where('action', 'query_sync')
                ->latest('id')
                ->first();

            if ($logEntry && $logEntry->status === 'success') {
                $response = json_decode($logEntry->response_payload, true);
                $successCount = $response['success'] ?? 0;
                $skippedCount = $response['skipped'] ?? 0;
                $fetchedCount = $response['fetched'] ?? null;

                $message = 'Sync completed successfully! ';
                if ($fetchedCount !== null) {
                    $message .= "SAP rows: {$fetchedCount}. ";
                }
                $message .= "Created: {$successCount} record(s), ";
                $message .= "Skipped: {$skippedCount} record(s)";

                return redirect()->route('admin.sap-sync-ito')
                    ->with('success', $message)
                    ->with('sync_results', [
                        'fetched' => $fetchedCount,
                        'success' => $successCount,
                        'skipped' => $skippedCount,
                    ]);
            } else {
                $errorMessage = $logEntry->error_message ?? 'Unknown error occurred';

                return redirect()->route('admin.sap-sync-ito')
                    ->with('error', 'Sync failed: '.substr($errorMessage, 0, 200));
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.sap-sync-ito')
                ->with('error', 'Sync failed: '.$e->getMessage());
        }
    }

    public function signatureVerify(Request $request, AdditionalDocument $additionalDocument)
    {
        $this->authorizeSignatureActions($additionalDocument);

        if (! $additionalDocument->requiresSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'This document type does not require signature verification.',
            ], 422);
        }

        if (! $additionalDocument->attachment) {
            return response()->json([
                'success' => false,
                'message' => 'Upload a scan attachment before verifying signatures.',
            ], 422);
        }

        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
        ]);

        $additionalDocument->update([
            'signature_project_id' => $validated['project_id'],
            'signature_status' => 'pending',
            'signature_override_reason' => null,
            'signature_override_by' => null,
            'signature_override_at' => null,
            'signature_checked_by' => null,
            'signature_checked_at' => null,
        ]);

        VerifyDocumentSignatureJob::dispatch($additionalDocument->id);

        return response()->json([
            'success' => true,
            'message' => 'Signature verification started.',
            'signature_status' => 'pending',
        ]);
    }

    public function signatureStatus(AdditionalDocument $additionalDocument)
    {
        $this->authorizeSignatureView($additionalDocument);

        $topK = (int) config('services.openrouter.signature_top_k', 3);
        $latestRunAt = SignatureMatchResult::query()
            ->where('additional_document_id', $additionalDocument->id)
            ->max('created_at');

        $results = collect();
        if ($latestRunAt) {
            $results = SignatureMatchResult::query()
                ->where('additional_document_id', $additionalDocument->id)
                ->where('created_at', $latestRunAt)
                ->with('specimen')
                ->orderByDesc('score')
                ->limit($topK)
                ->get()
                ->map(function (SignatureMatchResult $result): array {
                    $raw = json_decode($result->raw_response ?? '', true);
                    $documentCrop = is_array($raw) ? ($raw['document_signature_crop'] ?? null) : null;
                    $specimenCrop = is_array($raw) ? ($raw['specimen_signature_crop'] ?? null) : null;
                    $reasoning = is_array($raw) ? ($raw['reasoning'] ?? null) : null;

                    return [
                        'specimen_id' => $result->specimen_id,
                        'specimen_name' => $result->specimen?->name,
                        'score' => $result->score !== null ? (float) $result->score : null,
                        'verdict' => $result->verdict,
                        'reasoning' => $reasoning,
                        'document_crop' => $documentCrop,
                        'specimen_crop' => $specimenCrop,
                    ];
                });
        }

        return response()->json([
            'success' => true,
            'signature_status' => $additionalDocument->signature_status,
            'signature_project_id' => $additionalDocument->signature_project_id,
            'signature_checked_at' => $additionalDocument->signature_checked_at?->toIso8601String(),
            'signature_override_reason' => $additionalDocument->signature_override_reason,
            'results' => $results,
            'is_processing' => $additionalDocument->signature_status === 'pending',
        ]);
    }

    public function signatureConfirm(Request $request, AdditionalDocument $additionalDocument)
    {
        $this->authorizeSignatureActions($additionalDocument);

        $validated = $request->validate([
            'specimen_id' => ['required', 'exists:signature_specimens,id'],
        ]);

        $specimen = SignatureSpecimen::query()->findOrFail($validated['specimen_id']);

        $additionalDocument->update([
            'signature_status' => 'matched',
            'signature_checked_by' => Auth::id(),
            'signature_checked_at' => now(),
            'signature_override_reason' => null,
            'signature_override_by' => null,
            'signature_override_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Signature confirmed as '.$specimen->name.'.',
            'signature_status' => 'matched',
        ]);
    }

    public function signatureOverride(Request $request, AdditionalDocument $additionalDocument)
    {
        $this->authorizeSignatureActions($additionalDocument);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $additionalDocument->update([
            'signature_status' => 'no_match',
            'signature_override_reason' => $validated['reason'],
            'signature_override_by' => Auth::id(),
            'signature_override_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Signature override recorded.',
            'signature_status' => 'no_match',
        ]);
    }

    private function authorizeSignatureView(AdditionalDocument $additionalDocument): void
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['admin', 'superadmin', 'accounting', 'finance'])) {
            $userLocationCode = $user->department_location_code;
            if ($userLocationCode && $additionalDocument->cur_loc !== $userLocationCode) {
                abort(403, 'You do not have permission to view signature status for this document.');
            }
        }
    }

    private function authorizeSignatureActions(AdditionalDocument $additionalDocument): void
    {
        $this->authorizeSignatureView($additionalDocument);

        $user = Auth::user();
        if (! $user->hasAnyRole(['admin', 'superadmin', 'accounting', 'finance'])) {
            abort(403, 'You do not have permission to manage signature verification.');
        }
    }

    private function findDuplicateAdditionalDocument(
        ?int $typeId,
        ?string $documentNumber,
        ?string $vendorCode,
        ?int $excludeId = null
    ): ?AdditionalDocument {
        if ($typeId === null || $documentNumber === null || trim($documentNumber) === '') {
            return null;
        }

        $query = AdditionalDocument::query()
            ->where('type_id', $typeId)
            ->where('document_number', trim($documentNumber));

        if ($vendorCode !== null && trim($vendorCode) !== '') {
            $query->where('vendor_code', trim($vendorCode));
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    private function dispatchSignatureVerificationIfNeeded(AdditionalDocument $document, ?string $projectCode = null): void
    {
        $document->loadMissing('type');

        if (! $document->requiresSignature()) {
            return;
        }

        if (! $document->attachment) {
            $document->update(['signature_status' => 'skipped']);

            return;
        }

        $projectId = null;
        if ($projectCode) {
            $projectId = Project::query()->where('code', $projectCode)->value('id');
        }

        if (! $projectId) {
            $document->update(['signature_status' => 'skipped']);

            return;
        }

        $document->update([
            'signature_status' => 'pending',
            'signature_project_id' => $projectId,
            'signature_override_reason' => null,
            'signature_override_by' => null,
            'signature_override_at' => null,
            'signature_checked_by' => null,
            'signature_checked_at' => null,
        ]);

        VerifyDocumentSignatureJob::dispatch($document->id);
    }
}
