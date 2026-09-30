<?php

namespace App\Services\Logistics;

use App\Models\DeliveryPartEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DeliveryPartAssembler
{
    /**
     * @param  Collection<int, array<string, mixed>>  $sapRows
     * @param  Collection<int, string>  $warehouseCodes
     * @param  Collection<int, DeliveryPartEntry>  $entries
     * @return Collection<int, array<string, mixed>>
     */
    public function assemble(
        Collection $sapRows,
        Collection $warehouseCodes,
        Collection $entries,
        int $projectId,
    ): Collection {
        $warehouseSet = $warehouseCodes->flip();
        $entriesByKey = $entries->keyBy(fn (DeliveryPartEntry $entry) => $this->lineKey(
            $entry->ito_no,
            $entry->item_code,
            $entry->unit_no,
        ));

        $merged = collect();
        $sapKeys = collect();

        foreach ($sapRows as $sapRow) {
            $toWarehouse = $sapRow['to_warehouse'] ?? null;
            if ($toWarehouse === null || ! $warehouseSet->has($toWarehouse)) {
                continue;
            }

            $key = $this->lineKey(
                $sapRow['ito_no'] ?? null,
                $sapRow['item_code'] ?? null,
                $sapRow['unit_no'] ?? null,
            );
            $sapKeys->put($key, true);

            $entry = $entriesByKey->get($key);
            $merged->push($this->mergeSapRow($sapRow, $entry, $projectId));
        }

        foreach ($entries as $entry) {
            if ($entry->source !== DeliveryPartEntry::SOURCE_MANUAL) {
                continue;
            }

            $key = $this->lineKey($entry->ito_no, $entry->item_code, $entry->unit_no);
            if ($sapKeys->has($key)) {
                continue;
            }

            if ((int) $entry->project_id !== $projectId) {
                continue;
            }

            $merged->push($this->manualRow($entry));
        }

        return $merged->values();
    }

    /**
     * @param  array<string, mixed>  $sapRow
     * @return array<string, mixed>
     */
    private function mergeSapRow(array $sapRow, ?DeliveryPartEntry $entry, int $projectId): array
    {
        $itoNoSap = $sapRow['ito_no'] ?? null;
        $displayIto = $entry?->ito_no_override ?? $itoNoSap;

        return [
            'project_id' => $projectId,
            'entry_id' => $entry?->id,
            'doc_entry' => $sapRow['doc_entry'] ?? null,
            'to_warehouse' => $sapRow['to_warehouse'] ?? null,
            'source' => DeliveryPartEntry::SOURCE_SAP,
            'tanggal_received' => $this->formatDate($sapRow['ito_date'] ?? null),
            'supplier' => $sapRow['vendor'] ?? null,
            'po_number' => $sapRow['po_no'] ?? null,
            'no_spb' => $entry?->no_spb,
            'no_ito' => $displayIto,
            'ito_no_sap' => $itoNoSap,
            'ito_no_override' => $entry?->ito_no_override,
            'no_unit' => $sapRow['unit_no'] ?? null,
            'parts_number' => $sapRow['item_code'] ?? null,
            'descriptions' => $sapRow['description'] ?? null,
            'qty' => $sapRow['qty'] ?? null,
            'uom' => $sapRow['uom'] ?? null,
            'remarks_barang' => $entry?->remarks_barang,
            'tgl_delivery' => $this->formatDate($entry?->tgl_delivery),
            'transporter' => $entry?->transporter,
            'unit_kendaraan' => $entry?->unit_kendaraan,
            'ekspedisi' => $entry?->ekspedisi,
            'tgl_iti' => $this->formatDate($sapRow['iti_date'] ?? null),
            'no_iti' => $sapRow['iti_no'] ?? null,
            'keterangan' => filled($sapRow['iti_no'] ?? null) ? 'COMPLETE' : null,
            'ito_no' => $itoNoSap,
            'item_code' => $sapRow['item_code'] ?? null,
            'unit_no' => $sapRow['unit_no'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function manualRow(DeliveryPartEntry $entry): array
    {
        $displayIto = $entry->ito_no_override ?? $entry->ito_no;

        return [
            'project_id' => $entry->project_id,
            'entry_id' => $entry->id,
            'doc_entry' => null,
            'to_warehouse' => null,
            'source' => DeliveryPartEntry::SOURCE_MANUAL,
            'tanggal_received' => null,
            'supplier' => null,
            'po_number' => null,
            'no_spb' => $entry->no_spb,
            'no_ito' => $displayIto,
            'ito_no_sap' => null,
            'ito_no_override' => $entry->ito_no_override,
            'no_unit' => $entry->unit_no,
            'parts_number' => $entry->item_code,
            'descriptions' => null,
            'qty' => null,
            'uom' => null,
            'remarks_barang' => $entry->remarks_barang,
            'tgl_delivery' => $this->formatDate($entry->tgl_delivery),
            'transporter' => $entry->transporter,
            'unit_kendaraan' => $entry->unit_kendaraan,
            'ekspedisi' => $entry->ekspedisi,
            'tgl_iti' => null,
            'no_iti' => null,
            'keterangan' => null,
            'ito_no' => $entry->ito_no,
            'item_code' => $entry->item_code,
            'unit_no' => $entry->unit_no,
        ];
    }

    public function lineKey(?string $itoNo, ?string $itemCode, ?string $unitNo): string
    {
        return implode("\0", [$itoNo ?? '', $itemCode ?? '', $unitNo ?? '']);
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        return Carbon::parse((string) $value)->toDateString();
    }
}
