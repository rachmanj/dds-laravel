<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceAttachmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Check if user has permission to view invoice attachments
        if (!\Illuminate\Support\Facades\Auth::user() || !\Illuminate\Support\Facades\Auth::user()->can('inv-attachment-view')) {
            abort(403, 'You do not have permission to view invoice attachments.');
        }

        return view('invoices.attachments.index');
    }

    public function update(Request $request, InvoiceAttachment $attachment)
    {
        // Check if user has permission to edit invoice attachments
        if (!\Illuminate\Support\Facades\Auth::user() || !\Illuminate\Support\Facades\Auth::user()->can('inv-attachment-edit')) {
            abort(403, 'You do not have permission to edit invoice attachments.');
        }

        // Check if user can edit this attachment
        $user = auth()->user();
        if (!$user->hasRole(['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $attachment->invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only edit attachments from invoices in your department location.');
            }
        }

        $request->validate([
            'description' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);

        $attachment->update([
            'description' => $request->description,
            'category' => $request->category,
        ]);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Attachment updated successfully.',
                'attachment' => $attachment
            ]);
        }

        return redirect()->back()->with('success', 'Attachment updated successfully.');
    }

    public function store(Request $request, Invoice $invoice)
    {
        // Check if user has permission to create invoice attachments
        if (!\Illuminate\Support\Facades\Auth::user() || !\Illuminate\Support\Facades\Auth::user()->can('inv-attachment-create')) {
            abort(403, 'You do not have permission to upload invoice attachments.');
        }

        // Check if user can upload attachments to this invoice
        $user = Auth::user();
        if (!$user->hasRole(['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only upload attachments to invoices from your department location.');
            }
        }

        $request->validate([
            'files.*' => ['required', 'file', 'max:51200', 'mimes:pdf,jpg,jpeg,png,gif,webp'],
            'description' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);

        $uploadedFiles = [];
        $errors = [];

        foreach ($request->file('files') as $file) {
            try {
                // Generate unique filename
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $filename = Str::random(40) . '.' . $extension;

                // Store file
                $path = $file->storeAs(
                    'invoices/' . date('Y/m') . '/' . $invoice->id,
                    $filename,
                    'local'
                );

                // Create attachment record
                $attachment = InvoiceAttachment::create([
                    'invoice_id' => $invoice->id,
                    'file_name' => $originalName,
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'description' => $request->description,
                    'category' => $request->category,
                    'uploaded_by' => Auth::id(),
                ]);

                $uploadedFiles[] = $attachment;
            } catch (\Exception $e) {
                $errors[] = "Failed to upload {$originalName}: " . $e->getMessage();
            }
        }

        if ($request->ajax()) {
            if (empty($errors)) {
                return response()->json([
                    'success' => true,
                    'message' => count($uploadedFiles) . ' file(s) uploaded successfully.',
                    'attachments' => $uploadedFiles
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Some files failed to upload.',
                    'errors' => $errors,
                    'uploaded' => $uploadedFiles
                ], 422);
            }
        }

        // For non-AJAX requests, redirect with session messages
        if (empty($errors)) {
            return redirect()->route('invoices.attachments.show', $invoice)
                ->with('success', count($uploadedFiles) . ' file(s) uploaded successfully.');
        } else {
            return redirect()->route('invoices.attachments.show', $invoice)
                ->with('warning', 'Some files failed to upload.')
                ->with('errors', $errors);
        }
    }

    public function download(InvoiceAttachment $attachment)
    {
        // Check if user has permission to view/download invoice attachments
        if (!\Illuminate\Support\Facades\Auth::user() || !\Illuminate\Support\Facades\Auth::user()->can('inv-attachment-view')) {
            abort(403, 'You do not have permission to download invoice attachments.');
        }

        // Check if user can download this attachment
        $user = Auth::user();
        if (!$user->hasRole(['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $attachment->invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only download attachments from invoices in your department location.');
            }
        }

        if (!Storage::exists($attachment->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::download($attachment->file_path, $attachment->file_name);
    }

    public function destroy(InvoiceAttachment $attachment)
    {
        // Check if user has permission to delete invoice attachments
        if (!\Illuminate\Support\Facades\Auth::user() || !\Illuminate\Support\Facades\Auth::user()->can('inv-attachment-delete')) {
            abort(403, 'You do not have permission to delete invoice attachments.');
        }

        // Check if user can delete this attachment
        $user = Auth::user();
        if (!$user->hasRole(['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $attachment->invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only delete attachments from invoices in your department location.');
            }
        }

        $invoice = $attachment->invoice;

        // Delete physical file
        if (Storage::exists($attachment->file_path)) {
            Storage::delete($attachment->file_path);
        }

        // Delete database record
        $attachment->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully.'
            ]);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Attachment deleted successfully.');
    }

    public function preview(InvoiceAttachment $attachment)
    {
        // Check if user has permission to view invoice attachments
        if (!\Illuminate\Support\Facades\Auth::user() || !\Illuminate\Support\Facades\Auth::user()->can('inv-attachment-view')) {
            abort(403, 'You do not have permission to view invoice attachments.');
        }

        // Check if user can view this attachment
        $user = Auth::user();
        if (!$user->hasRole(['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $attachment->invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only view attachments from invoices in your department location.');
            }
        }

        if (!Storage::exists($attachment->file_path)) {
            abort(404, 'File not found.');
        }

        // For images and PDFs, we can show inline
        if ($attachment->isImage() || $attachment->isPdf()) {
            return response()->file(Storage::path($attachment->file_path));
        }

        // For other files, force download
        return Storage::download($attachment->file_path, $attachment->file_name);
    }

    public function show(Invoice $invoice)
    {
        // Check if user has permission to view invoice attachments
        if (!Auth::user() || !Auth::user()->can('inv-attachment-view')) {
            abort(403, 'You do not have permission to view invoice attachments.');
        }

        // Check if user can view this invoice's attachments
        $user = Auth::user();
        if (!$user->hasRole(['superadmin', 'admin'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                abort(403, 'You can only view attachments from invoices in your department location.');
            }
        }

        // Load invoice with all necessary relationships
        $invoice->load(['supplier', 'type', 'creator', 'attachments.uploader', 'receiveProjectInfo', 'invoiceProjectInfo', 'paymentProjectInfo']);

        return view('invoices.attachments.show', compact('invoice'));
    }

    /**
     * Format file size in human readable format
     */
    /**
     * Get data for DataTables
     */
    public function data(Request $request)
    {
        if (!\Illuminate\Support\Facades\Auth::user() || !\Illuminate\Support\Facades\Auth::user()->can('inv-attachment-view')) {
            abort(403, 'You do not have permission to view invoice attachments.');
        }

        $user = auth()->user();
        $query = Invoice::with(['attachments.uploader', 'supplier', 'department']);

        // Filter by user's department location if not admin/superadmin
        if (!$user->hasRole(['superadmin', 'admin']) && !$request->boolean('show_all', false)) {
            $locationCode = $user->department_location_code;
            if ($locationCode) {
                $query->where('cur_loc', $locationCode);
            }
        }

        // Filters
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        // Caution: Non-admin cannot override to different location
        if ($request->filled('cur_loc')) {
            $requestedLoc = $request->string('cur_loc')->toString();
            if ($user->hasRole(['superadmin', 'admin'])) {
                $query->where('cur_loc', $requestedLoc);
            } else {
                $locationCode = $user->department_location_code;
                if ($locationCode) {
                    $query->where('cur_loc', $locationCode);
                }
            }
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $from = $request->date('date_from');
            $to = $request->date('date_to');
            if ($from && $to) {
                $query->whereBetween('invoice_date', [$from->startOfDay(), $to->endOfDay()]);
            } elseif ($from) {
                $query->where('invoice_date', '>=', $from->startOfDay());
            } elseif ($to) {
                $query->where('invoice_date', '<=', $to->endOfDay());
            }
        }

        // Attachment status: has_attachments = yes|no
        if ($request->filled('has_attachments')) {
            $flag = $request->string('has_attachments')->toString();
            if ($flag === 'yes') {
                $query->has('attachments');
            } elseif ($flag === 'no') {
                $query->doesntHave('attachments');
            }
        }

        // Specific field searches
        if ($invoiceNumber = $request->string('invoice_number')->toString()) {
            $query->where('invoice_number', 'like', "%{$invoiceNumber}%");
        }
        if ($po = $request->string('po_no')->toString()) {
            $query->where('po_no', 'like', "%{$po}%");
        }
        if ($supplierName = $request->string('supplier_name')->toString()) {
            $query->whereHas('supplier', function ($q) use ($supplierName) {
                $q->where('name', 'like', "%{$supplierName}%");
            });
        }

        return datatables()->of($query)
            ->addColumn('total_attachments', function ($invoice) {
                return $invoice->attachments->count();
            })
            ->addColumn('total_size', function ($invoice) {
                $totalSize = $invoice->attachments->sum('file_size');
                return $invoice->attachments->count() > 0 ? $this->formatFileSize($totalSize) : 'No attachments';
            })
            ->addColumn('last_uploaded', function ($invoice) {
                $lastUploaded = $invoice->attachments->max('created_at');
                return $lastUploaded ? $lastUploaded->format('d-M-Y H:i') : 'No attachments';
            })
            ->addColumn('last_uploader', function ($invoice) {
                $lastUploaded = $invoice->attachments->max('created_at');
                $lastUploader = $invoice->attachments->where('created_at', $lastUploaded)->first()?->uploader;
                return $lastUploader?->name ?? 'No attachments';
            })
            ->addColumn('actions', function ($invoice) {
                $detailButton = ' <a class="btn btn-xs btn-primary" href="' . route('invoices.attachments.show', $invoice) . '" title="Detail"><i class="fas fa-list"></i></a>';
                return $detailButton;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Format file size in human readable format
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
