<?php

namespace App\Http\Controllers;

use App\Jobs\ExtractInvoiceFromDocumentJob;
use App\Services\InvoiceImportAttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class InvoiceImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function extract(Request $request)
    {
        if (! config('services.openrouter.enabled', true)) {
            abort(404);
        }
        if (! config('services.openrouter.key')) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice import is not configured (API key missing).',
            ], 503);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:15360', 'mimes:pdf,jpg,jpeg,png,webp,gif'],
        ]);

        $uuid = (string) Str::uuid();
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $path = $file->storeAs('temp/invoice-imports', $uuid.'.'.$extension, 'local');

        $key = InvoiceImportAttachmentService::cacheKey($uuid);
        Cache::put($key, [
            'user_id' => Auth::id(),
            'status' => 'pending',
            'path' => $path,
            'mime' => $file->getMimeType(),
            'original_name' => $file->getClientOriginalName(),
        ], now()->addMinutes(120));

        if (config('services.openrouter.extract_sync', false)) {
            ExtractInvoiceFromDocumentJob::dispatchSync($uuid);
        } else {
            ExtractInvoiceFromDocumentJob::dispatch($uuid);
        }

        $after = Cache::get($key);
        $jobStatus = is_array($after) ? ($after['status'] ?? 'queued') : 'queued';

        return response()->json([
            'success' => true,
            'uuid' => $uuid,
            'status' => $jobStatus,
            'error' => is_array($after) ? ($after['error'] ?? null) : null,
        ]);
    }

    public function status(string $uuid)
    {
        $key = InvoiceImportAttachmentService::cacheKey($uuid);
        $data = Cache::get($key);
        if (! is_array($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Import session not found or expired.',
            ], 404);
        }
        if (($data['user_id'] ?? null) !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'status' => $data['status'] ?? 'unknown',
            'error' => $data['error'] ?? null,
        ]);
    }

    public function draft(string $uuid)
    {
        $key = InvoiceImportAttachmentService::cacheKey($uuid);
        $data = Cache::get($key);
        if (! is_array($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Import session not found or expired.',
            ], 404);
        }
        if (($data['user_id'] ?? null) !== Auth::id()) {
            abort(403);
        }
        if (($data['status'] ?? '') !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Import not ready yet.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'draft' => $data['draft'] ?? [],
        ]);
    }
}
