<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessItoBatchImportJob;
use App\Jobs\VerifyDocumentSignatureJob;
use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\ItoBatchImport;
use App\Models\ItoBatchItem;
use App\Models\Project;
use App\Services\ItoBatchImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ItoBatchImportController extends Controller
{
    public function __construct(
        private ItoBatchImportService $batchService
    ) {
        $this->middleware('auth');
        $this->middleware('permission:manage-ito-batch-import');
    }

    public function index(): View
    {
        return view('admin.ito_batch_import.index');
    }

    public function data()
    {
        $batches = ItoBatchImport::query()
            ->with('creator')
            ->withCount([
                'items as matched_count' => fn ($q) => $q->where('status', 'matched'),
                'items as review_count' => fn ($q) => $q->whereIn('status', ['not_found', 'ambiguous', 'low_confidence']),
            ])
            ->orderByDesc('created_at');

        return DataTables::of($batches)
            ->addColumn('creator_name', fn (ItoBatchImport $batch) => $batch->creator?->name ?? '-')
            ->addColumn('status_badge', function (ItoBatchImport $batch) {
                $class = match ($batch->status) {
                    'processed' => 'success',
                    'partial' => 'warning',
                    'processing' => 'info',
                    'failed' => 'danger',
                    default => 'secondary',
                };

                return '<span class="badge badge-'.$class.'">'.e(ucfirst($batch->status)).'</span>';
            })
            ->addColumn('summary', function (ItoBatchImport $batch) {
                return $batch->matched_count.' matched / '.$batch->review_count.' review';
            })
            ->addColumn('actions', function (ItoBatchImport $batch) {
                $actions = '<div class="btn-group" style="gap:2px;">';
                $actions .= '<a href="'.route('ito-batch-import.show', $batch).'" class="btn btn-info btn-xs" title="View"><i class="fas fa-eye"></i></a>';
                if ($batch->review_count > 0) {
                    $actions .= '<a href="'.route('ito-batch-import.review', $batch).'" class="btn btn-warning btn-xs" title="Review"><i class="fas fa-tasks"></i></a>';
                }
                $actions .= '</div>';

                return $actions;
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ]);

        $file = $request->file('pdf');
        $storedPath = $file->storeAs(
            'ito-batch-imports',
            time().'_'.$file->getClientOriginalName(),
            'public'
        );

        $batch = ItoBatchImport::query()->create([
            'filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'total_pages' => 0,
            'status' => 'pending',
            'created_by' => Auth::id(),
        ]);

        ProcessItoBatchImportJob::dispatch($batch->id);

        return redirect()
            ->route('ito-batch-import.show', $batch)
            ->with('success', 'Batch uploaded successfully. Processing has started.');
    }

    public function show(ItoBatchImport $batch): View
    {
        $batch->load(['items.matchedDocument', 'creator']);

        return view('admin.ito_batch_import.show', compact('batch'));
    }

    public function review(ItoBatchImport $batch): View
    {
        $items = $batch->items()
            ->whereIn('status', ['not_found', 'ambiguous', 'low_confidence'])
            ->orderBy('page_number')
            ->get();

        $itoTypeId = AdditionalDocumentType::query()->where('type_name', 'ITO')->value('id');
        $itoDocuments = AdditionalDocument::query()
            ->when($itoTypeId, fn ($q) => $q->where('type_id', $itoTypeId))
            ->orderByDesc('created_at')
            ->limit(500)
            ->get(['id', 'document_number', 'document_date', 'po_no', 'attachment']);

        return view('admin.ito_batch_import.review', compact('batch', 'items', 'itoDocuments'));
    }

    public function assign(Request $request, ItoBatchItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'document_id' => ['required', 'exists:additional_documents,id'],
        ]);

        $document = AdditionalDocument::query()->findOrFail($validated['document_id']);
        $this->assertItoDocument($document);

        $batchNo = $this->batchService->nextBatchNo();
        $this->batchService->attachPage($item, $document, $batchNo);

        $item->update([
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);

        $this->dispatchSignatureVerification($document->id);
        $this->refreshBatchStatus($item->batch);

        return redirect()
            ->route('ito-batch-import.review', $item->batch_id)
            ->with('success', 'Page assigned and attached to document '.$document->document_number.'.');
    }

    public function createAndAttach(Request $request, ItoBatchItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'document_number' => ['required', 'string', 'max:255'],
            'document_date' => ['nullable', 'date'],
            'po_no' => ['nullable', 'string', 'max:255'],
            'project' => ['nullable', 'string', 'max:255'],
            'receive_date' => ['nullable', 'date'],
        ]);

        $itoType = AdditionalDocumentType::query()->where('type_name', 'ITO')->firstOrFail();
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $document = AdditionalDocument::query()->create([
            'type_id' => $itoType->id,
            'document_number' => $validated['document_number'],
            'document_date' => $validated['document_date'] ?? now()->toDateString(),
            'po_no' => $validated['po_no'] ?? null,
            'project' => $validated['project'] ?? $user->project,
            'receive_date' => $validated['receive_date'] ?? now()->toDateString(),
            'created_by' => $user->id,
            'cur_loc' => $user->department_location_code ?: 'DEFAULT',
            'status' => 'open',
        ]);

        $this->setSignatureProjectIfNeeded($document);

        $batchNo = $this->batchService->nextBatchNo();
        $this->batchService->attachPage($item, $document, $batchNo);

        $item->update([
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);

        $this->dispatchSignatureVerification($document->id);
        $this->refreshBatchStatus($item->batch);

        return redirect()
            ->route('ito-batch-import.review', $item->batch_id)
            ->with('success', 'New ITO document created and page attached.');
    }

    public function skip(ItoBatchItem $item): RedirectResponse
    {
        $item->update([
            'status' => 'skipped',
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);

        $this->refreshBatchStatus($item->batch);

        return redirect()
            ->route('ito-batch-import.review', $item->batch_id)
            ->with('success', 'Page skipped.');
    }

    private function assertItoDocument(AdditionalDocument $document): void
    {
        $document->loadMissing('type');
        if ($document->type?->type_name !== 'ITO') {
            abort(422, 'Selected document is not an ITO record.');
        }
    }

    private function setSignatureProjectIfNeeded(AdditionalDocument $document): void
    {
        if (! $document->requiresSignature()) {
            return;
        }

        $projectId = null;
        if ($document->project) {
            $projectId = Project::query()->where('code', $document->project)->value('id');
        }

        $document->update([
            'signature_status' => $projectId ? 'pending' : 'skipped',
            'signature_project_id' => $projectId,
        ]);
    }

    private function dispatchSignatureVerification(int $documentId): void
    {
        try {
            VerifyDocumentSignatureJob::dispatch($documentId);
        } catch (\Throwable $e) {
            Log::warning('ITO batch import controller: signature verification dispatch failed', [
                'document_id' => $documentId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function refreshBatchStatus(ItoBatchImport $batch): void
    {
        $batch->refresh();
        $reviewCount = $batch->reviewNeededCount();

        if ($batch->status === 'processing' || $batch->status === 'pending') {
            return;
        }

        $batch->update([
            'status' => $reviewCount > 0 ? 'partial' : 'processed',
        ]);
    }
}
