<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\LogisticsInventoryItem;
use App\Models\LogisticsInventoryPivot;
use App\Models\LogisticsInventorySnapshot;
use App\Models\LogisticsItemCategory;
use App\Services\Logistics\ItemCategoryResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CategoryMapController extends Controller
{
    public function index(): View
    {
        $mappings = LogisticsItemCategory::query()
            ->with('updatedBy')
            ->orderBy('prefix')
            ->get();

        $displaySnapshot = $this->latestSuccessfulSnapshot();

        $itemCountByCategory = [];
        $usedCategories = [];

        if ($displaySnapshot !== null) {
            $itemCountByCategory = LogisticsInventoryItem::query()
                ->where('snapshot_id', $displaySnapshot->id)
                ->selectRaw('category, COUNT(*) as item_count')
                ->groupBy('category')
                ->orderBy('category')
                ->pluck('item_count', 'category')
                ->all();

            $usedCategories = LogisticsItemCategory::query()
                ->where('is_active', true)
                ->distinct()
                ->orderBy('category')
                ->pluck('category')
                ->all();
        }

        return view('logistics.categories', [
            'mappings' => $mappings,
            'itemCountByCategory' => $itemCountByCategory,
            'usedCategories' => $usedCategories,
            'snapshotDate' => $displaySnapshot?->snapshot_date,
            'hasSnapshot' => $displaySnapshot !== null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prefix' => ['required', 'string', 'max:50', 'unique:logistics_item_categories,prefix'],
            'category' => ['required', 'string', 'max:255'],
        ], [
            'prefix.required' => 'Prefix wajib diisi.',
            'prefix.unique' => 'Prefix sudah digunakan.',
            'category.required' => 'Kategori wajib diisi.',
        ]);

        LogisticsItemCategory::query()->create([
            'prefix' => strtoupper(trim($validated['prefix'])),
            'category' => trim($validated['category']),
            'is_active' => true,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('logistics.categories.index')
            ->with('success', 'Mapping prefix berhasil ditambahkan.');
    }

    public function update(Request $request, LogisticsItemCategory $logisticsItemCategory): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:255'],
        ], [
            'category.required' => 'Kategori wajib diisi.',
        ]);

        $logisticsItemCategory->update([
            'category' => trim($validated['category']),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('logistics.categories.index')
            ->with('success', 'Kategori prefix berhasil diperbarui.');
    }

    public function toggle(Request $request, LogisticsItemCategory $logisticsItemCategory): RedirectResponse
    {
        $logisticsItemCategory->update([
            'is_active' => ! $logisticsItemCategory->is_active,
            'updated_by' => $request->user()->id,
        ]);

        $status = $logisticsItemCategory->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('logistics.categories.index')
            ->with('success', "Prefix {$logisticsItemCategory->prefix} berhasil {$status}.");
    }

    public function recompute(ItemCategoryResolver $categoryResolver): RedirectResponse
    {
        $displaySnapshot = $this->latestSuccessfulSnapshot();

        if ($displaySnapshot === null) {
            return redirect()
                ->route('logistics.categories.index')
                ->with('error', 'Tidak ada snapshot inventory sukses yang dapat diperbarui.');
        }

        $changedCount = 0;

        DB::transaction(function () use ($displaySnapshot, $categoryResolver, &$changedCount): void {
            $items = LogisticsInventoryItem::query()
                ->where('snapshot_id', $displaySnapshot->id)
                ->get();

            $pivotAccumulator = [];

            foreach ($items as $item) {
                $newCategory = $categoryResolver->resolve((string) $item->item_code);

                if ($newCategory !== $item->category) {
                    $changedCount++;
                    $item->update(['category' => $newCategory]);
                }

                $project = $item->project;
                $pivotKey = ($project ?? '').'|'.$newCategory;

                if (! isset($pivotAccumulator[$pivotKey])) {
                    $pivotAccumulator[$pivotKey] = [
                        'project' => $project,
                        'category' => $newCategory,
                        'sum_instock' => 0.0,
                        'sum_value' => 0.0,
                    ];
                }

                $pivotAccumulator[$pivotKey]['sum_instock'] += (float) $item->instock;
                $pivotAccumulator[$pivotKey]['sum_value'] += (float) $item->total_value;
            }

            LogisticsInventoryPivot::query()
                ->where('snapshot_id', $displaySnapshot->id)
                ->delete();

            foreach ($pivotAccumulator as $pivot) {
                LogisticsInventoryPivot::query()->create([
                    'snapshot_id' => $displaySnapshot->id,
                    'project' => $pivot['project'],
                    'category' => $pivot['category'],
                    'sum_instock' => $pivot['sum_instock'],
                    'sum_value' => $pivot['sum_value'],
                ]);
            }
        });

        return redirect()
            ->route('logistics.categories.index')
            ->with('success', "Kategori snapshot terakhir diperbarui. {$changedCount} item berubah kategori.");
    }

    private function latestSuccessfulSnapshot(): ?LogisticsInventorySnapshot
    {
        return LogisticsInventorySnapshot::query()
            ->where('status', 'success')
            ->latest('id')
            ->first();
    }
}
