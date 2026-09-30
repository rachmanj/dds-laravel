<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeliveryPartSpbRequest;
use App\Http\Requests\UpdateDeliveryPartSpbRequest;
use App\Models\DeliveryPartSpb;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DeliveryPartSpbController extends Controller
{
    public function data(Request $request): JsonResponse
    {
        $query = DeliveryPartSpb::query()
            ->with(['project', 'createdBy', 'items'])
            ->withCount('items');

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        } elseif ($request->filled('project')) {
            $project = Project::query()->where('code', $request->string('project')->toString())->first();
            if ($project !== null) {
                $query->where('project_id', $project->id);
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('tanggal', '>=', $request->string('from_date')->toString());
        }

        if ($request->filled('to_date')) {
            $query->whereDate('tanggal', '<=', $request->string('to_date')->toString());
        }

        $user = $request->user();
        $canEdit = $user?->can('edit-delivery-part') ?? false;

        return DataTables::of($query)
            ->addColumn('project_code', fn (DeliveryPartSpb $spb) => $spb->project?->code ?? '-')
            ->addColumn('tanggal_display', fn (DeliveryPartSpb $spb) => $spb->tanggal?->format('Y-m-d') ?? '-')
            ->addColumn('created_by_name', fn (DeliveryPartSpb $spb) => $spb->createdBy?->name ?? '-')
            ->addColumn('actions', function (DeliveryPartSpb $spb) use ($user, $canEdit) {
                if (! $canEdit) {
                    return '<button type="button" class="btn btn-xs btn-info btn-spb-detail" data-id="'.$spb->id.'"><i class="fas fa-eye"></i> Detail</button>';
                }

                $canDelete = $this->userCanDeleteSpb($user, $spb);
                $deleteBtn = $canDelete
                    ? '<button type="button" class="btn btn-xs btn-danger btn-spb-delete" data-id="'.$spb->id.'"><i class="fas fa-trash"></i></button>'
                    : '';

                return '<button type="button" class="btn btn-xs btn-info btn-spb-detail" data-id="'.$spb->id.'"><i class="fas fa-eye"></i></button> '
                    .'<button type="button" class="btn btn-xs btn-primary btn-spb-edit" data-id="'.$spb->id.'"><i class="fas fa-edit"></i></button> '
                    .$deleteBtn;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function show(DeliveryPartSpb $spb): JsonResponse
    {
        $spb->load(['project', 'items']);

        return response()->json([
            'id' => $spb->id,
            'project_id' => $spb->project_id,
            'project_code' => $spb->project?->code,
            'no_spb' => $spb->no_spb,
            'tanggal' => $spb->tanggal?->format('Y-m-d'),
            'remarks' => $spb->remarks,
            'items' => $spb->items->map(fn ($item) => [
                'part_number' => $item->part_number,
                'description' => $item->description,
                'qty' => $item->qty,
                'uom' => $item->uom,
                'remarks' => $item->remarks,
            ])->values(),
        ]);
    }

    public function store(StoreDeliveryPartSpbRequest $request): JsonResponse
    {
        $spb = DeliveryPartSpb::query()->create([
            'project_id' => $request->input('project_id'),
            'no_spb' => $request->input('no_spb'),
            'tanggal' => $request->input('tanggal'),
            'remarks' => $request->input('remarks'),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $this->syncItems($spb, $request->input('items', []));

        return response()->json([
            'message' => 'SPB berhasil disimpan.',
            'id' => $spb->id,
        ], 201);
    }

    public function update(UpdateDeliveryPartSpbRequest $request, DeliveryPartSpb $spb): JsonResponse
    {
        $spb->update([
            'project_id' => $request->input('project_id'),
            'no_spb' => $request->input('no_spb'),
            'tanggal' => $request->input('tanggal'),
            'remarks' => $request->input('remarks'),
            'updated_by' => $request->user()?->id,
        ]);

        $this->syncItems($spb, $request->input('items', []));

        return response()->json(['message' => 'SPB berhasil diperbarui.']);
    }

    public function destroy(Request $request, DeliveryPartSpb $spb): JsonResponse
    {
        if (! $this->userCanDeleteSpb($request->user(), $spb)) {
            return response()->json(['message' => 'Anda tidak berhak menghapus SPB ini.'], 403);
        }

        $spb->delete();

        return response()->json(['message' => 'SPB berhasil dihapus.']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(DeliveryPartSpb $spb, array $items): void
    {
        $spb->items()->delete();

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $spb->items()->create([
                'part_number' => $item['part_number'] ?? null,
                'description' => $item['description'] ?? null,
                'qty' => $item['qty'] ?? null,
                'uom' => $item['uom'] ?? null,
                'remarks' => $item['remarks'] ?? null,
            ]);
        }
    }

    private function userCanDeleteSpb(?\App\Models\User $user, DeliveryPartSpb $spb): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'superadmin'])) {
            return true;
        }

        return (int) $spb->created_by === (int) $user->id;
    }
}
