<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeliveryPartItoCancelRequest;
use App\Models\DeliveryPartItoCancel;
use App\Services\SapService;
use App\Support\ItoCancelGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DeliveryPartCancelController extends Controller
{
    public function __construct(
        private SapService $sapService,
        private ItoCancelGuard $guard,
    ) {}

    public function store(StoreDeliveryPartItoCancelRequest $request): JsonResponse
    {
        $docEntry = (int) $request->integer('doc_entry');
        $itoNo = $request->string('ito_no')->toString();
        $reason = $request->string('reason')->toString();

        $guardResult = $this->guard->validate($docEntry, $itoNo, $reason);
        if (! $guardResult['allowed']) {
            return response()->json(['message' => $guardResult['message']], 422);
        }

        $cancel = DeliveryPartItoCancel::query()->create([
            'doc_entry' => $docEntry,
            'ito_no' => $itoNo,
            'project_id' => $request->input('project_id'),
            'item_code' => $request->input('item_code'),
            'unit_no' => $request->input('unit_no'),
            'reason' => $reason,
            'status' => DeliveryPartItoCancel::STATUS_REQUESTED,
            'requested_by' => $request->user()?->id,
            'requested_at' => now(),
            'attempts' => 0,
        ]);

        $sapResult = $this->sapService->cancelStockTransfer($docEntry);
        $cancel->attempts = 1;
        $cancel->executed_at = now();

        if (! $sapResult['ok']) {
            $cancel->status = DeliveryPartItoCancel::STATUS_FAILED;
            $cancel->sap_message = $sapResult['message'];
            $cancel->save();

            return response()->json([
                'message' => 'Pembatalan gagal di SAP.',
                'cancel' => $this->formatCancelPayload($cancel),
            ], 422);
        }

        $verified = $this->sapService->isStockTransferCancelled($docEntry);

        if ($verified === true) {
            $cancel->status = DeliveryPartItoCancel::STATUS_CANCELLED;
            $cancel->verified_at = now();
            $cancel->sap_message = null;
            $cancel->save();

            return response()->json([
                'message' => 'ITO berhasil dibatalkan dan terverifikasi di SAP.',
                'cancel' => $this->formatCancelPayload($cancel),
            ]);
        }

        $cancel->status = DeliveryPartItoCancel::STATUS_REQUESTED;
        $cancel->sap_message = 'SAP menerima pembatalan (HTTP '.$sapResult['http_status'].'), tetapi status CANCELED belum terbaca. Permintaan tetap menunggu verifikasi.';
        $cancel->save();

        return response()->json([
            'message' => $cancel->sap_message,
            'cancel' => $this->formatCancelPayload($cancel),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = DeliveryPartItoCancel::query()
            ->with(['requestedBy:id,name', 'project:id,code'])
            ->orderByDesc('requested_at');

        if ($request->filled('project_id')) {
            $query->where('project_id', (int) $request->input('project_id'));
        }

        return DataTables::of($query)
            ->addColumn('user_name', fn (DeliveryPartItoCancel $row) => $row->requestedBy?->name ?? '-')
            ->addColumn('project_code', fn (DeliveryPartItoCancel $row) => $row->project?->code ?? '-')
            ->addColumn('requested_at_display', fn (DeliveryPartItoCancel $row) => $row->requested_at?->format('d-m-Y H:i') ?? '-')
            ->addColumn('status_label', function (DeliveryPartItoCancel $row) {
                return match ($row->status) {
                    DeliveryPartItoCancel::STATUS_CANCELLED => 'Dibatalkan (terverifikasi)',
                    DeliveryPartItoCancel::STATUS_FAILED => 'Gagal',
                    default => 'Menunggu verifikasi',
                };
            })
            ->make(true);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCancelPayload(DeliveryPartItoCancel $cancel): array
    {
        return [
            'id' => $cancel->id,
            'doc_entry' => $cancel->doc_entry,
            'ito_no' => $cancel->ito_no,
            'status' => $cancel->status,
            'sap_message' => $cancel->sap_message,
            'verified_at' => $cancel->verified_at?->toIso8601String(),
        ];
    }
}
