<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceAttachmentController extends Controller
{
    public function getInvoiceAttachments($invoiceId)
    {
        // Check if user has permission to view invoice attachments
        if (!\Illuminate\Support\Facades\Auth::user() || !\Illuminate\Support\Facades\Auth::user()->can('inv-attachment-view')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view invoice attachments.'
            ], 403);
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        $invoice = Invoice::with(['attachments.uploader', 'supplier'])->find($invoiceId);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found.'
            ], 404);
        }

        // Check if user can view this invoice's attachments
        if (!$user->hasAnyRole(['superadmin', 'admin', 'accounting'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode && $invoice->cur_loc !== $locationCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only view attachments from invoices in your department location.'
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'invoice' => $invoice,
                'attachments' => $invoice->attachments
            ]
        ]);
    }

    public function getAttachmentStats()
    {
        // Check if user has permission to view invoice attachments
        if (!\Illuminate\Support\Facades\Auth::user() || !\Illuminate\Support\Facades\Auth::user()->can('inv-attachment-view')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view invoice attachments.'
            ], 403);
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        $query = Invoice::with(['attachments.uploader']);

        // Filter by user's department location if not admin/superadmin/accounting
        if (!$user->hasAnyRole(['superadmin', 'admin', 'accounting'])) {
            $locationCode = $user->department_location_code;
            if ($locationCode) {
                $query->where('cur_loc', $locationCode);
            }
        }

        $invoices = $query->get();
        $totalInvoices = $invoices->count();

        $totalAttachments = 0;
        $totalSize = 0;
        $lastUpload = null;
        $fileTypeDistribution = [
            'images' => 0,
            'pdfs' => 0,
            'others' => 0,
        ];

        foreach ($invoices as $invoice) {
            $attachments = $invoice->attachments;
            $totalAttachments += $attachments->count();
            $totalSize += $attachments->sum('file_size');

            $invoiceLastUpload = $attachments->max('created_at');
            if ($invoiceLastUpload && (!$lastUpload || $invoiceLastUpload > $lastUpload)) {
                $lastUpload = $invoiceLastUpload;
            }

            foreach ($attachments as $attachment) {
                if ($attachment->isImage()) {
                    $fileTypeDistribution['images']++;
                } elseif ($attachment->isPdf()) {
                    $fileTypeDistribution['pdfs']++;
                } else {
                    $fileTypeDistribution['others']++;
                }
            }
        }

        // Recent uploads (last 5)
        $recentUploads = InvoiceAttachment::with(['uploader', 'invoice'])
            ->whereIn('invoice_id', $invoices->pluck('id'))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'file_name' => $a->file_name,
                    'mime_type' => $a->mime_type,
                    'file_size' => $a->formatted_file_size,
                    'uploaded_by' => optional($a->uploader)->name,
                    'invoice_number' => optional($a->invoice)->invoice_number,
                    'created_at' => $a->created_at?->format('d-M-Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_invoices' => $totalInvoices,
                'total_attachments' => $totalAttachments,
                'total_size' => $this->formatFileSize($totalSize),
                'last_upload' => $lastUpload ? $lastUpload->format('d-M-Y H:i') : 'No attachments',
                'file_type_distribution' => $fileTypeDistribution,
                'recent_uploads' => $recentUploads,
            ]
        ]);
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
