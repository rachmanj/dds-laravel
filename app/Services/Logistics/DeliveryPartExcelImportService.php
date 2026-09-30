<?php

namespace App\Services\Logistics;

use App\Exceptions\SapSqlQueryException;
use App\Models\DeliveryPartEntry;
use App\Models\LogisticsWarehouseProject;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class DeliveryPartExcelImportService
{
    public const SHEETS = ['017C', '022C', '026C', 'PRATASABA'];

    private const HEADER_ROW_PRIMARY = 5;

    private const HEADER_ROW_SECONDARY = 6;

    private const DATA_START_ROW = 7;

    /**
     * @var list<string>
     */
    private const MANUAL_FIELDS = [
        'no_spb',
        'remarks_barang',
        'tgl_delivery',
        'transporter',
        'unit_kendaraan',
        'ekspedisi',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const HEADER_ALIASES = [
        'ito_no' => ['NO ITO', 'NO. ITO', 'ITO NO'],
        'item_code' => ['PARTS NUMBER', 'PART NUMBER', 'PARTS NO', 'ITEM CODE'],
        'unit_no' => ['NO UNIT', 'NO. UNIT', 'UNIT NO'],
        'no_spb' => ['NO. SPB', 'NO SPB'],
        'remarks_barang' => ['REMARKS BARANG', 'REMARK BARANG'],
        'tgl_delivery' => ['TGL DELIVERY', 'TANGGAL DELIVERY'],
        'transporter' => ['TRANSPORTER'],
        'unit_kendaraan' => ['UNIT & NO KENDARAAN', 'UNIT AND NO KENDARAAN', 'UNIT NO KENDARAAN'],
        'ekspedisi' => ['EKSPEDISI', 'EKSPEDISI PENGIRIM', 'EXPEDISI', 'EXPEDISI PENGIRIM'],
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_HEADER_KEYS = [
        'ito_no',
        'item_code',
        'unit_no',
        'no_spb',
        'remarks_barang',
        'tgl_delivery',
        'transporter',
        'unit_kendaraan',
        'ekspedisi',
    ];

    public function __construct(
        private DeliveryPartQueryService $queryService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function import(string $filePath, bool $write): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $globalInserts = 0;
        $globalUpdates = 0;
        $sheetSummaries = [];

        $sapRowsByKey = null;
        $sapUnavailable = false;

        foreach (self::SHEETS as $sheetName) {
            $worksheet = $spreadsheet->getSheetByName($sheetName);
            if ($worksheet === null) {
                $sheetSummaries[$sheetName] = [
                    'skipped' => true,
                    'skip_reason' => 'Sheet tidak ditemukan.',
                ];

                continue;
            }

            $project = Project::query()->where('code', $sheetName)->first();
            if ($project === null) {
                $sheetSummaries[$sheetName] = [
                    'skipped' => true,
                    'skip_reason' => 'Project '.$sheetName.' tidak ada di database.',
                ];

                continue;
            }

            $headerMap = $this->buildHeaderMap($worksheet);
            $missing = $this->missingRequiredHeaders($headerMap);
            if ($missing !== []) {
                $sheetSummaries[$sheetName] = [
                    'skipped' => true,
                    'skip_reason' => 'Kolom wajib tidak ditemukan: '.implode(', ', $missing),
                ];

                continue;
            }

            $isPratasaba = $sheetName === 'PRATASABA';
            $sapKeyIndex = collect();

            if (! $isPratasaba) {
                if ($sapRowsByKey === null) {
                    [$sapRowsByKey, $sapUnavailable] = $this->loadSapIndex();
                }

                $warehouseCodes = LogisticsWarehouseProject::query()
                    ->where('project_id', $project->id)
                    ->where('is_active', true)
                    ->pluck('whs_code');

                $sapKeyIndex = $this->buildProjectSapKeyIndex($sapRowsByKey, $warehouseCodes);
            }

            $entries = DeliveryPartEntry::query()
                ->where('project_id', $project->id)
                ->get()
                ->keyBy(fn (DeliveryPartEntry $entry) => $this->matchKey(
                    $this->normalizeKeyPart($entry->ito_no),
                    $this->normalizeKeyPart($entry->item_code),
                    $this->normalizeUnitNo($entry->unit_no),
                ));

            $summary = [
                'skipped' => false,
                'rows_read' => 0,
                'rows_empty_skipped' => 0,
                'matched' => 0,
                'will_create_sap' => 0,
                'will_create_manual' => 0,
                'will_fill' => 0,
                'conflicts' => 0,
                'write_failures' => 0,
                'notes' => [],
                'failure_messages' => [],
            ];

            if ($sapUnavailable && ! $isPratasaba) {
                $summary['notes'][] = 'Koneksi SAP tidak tersedia; baris baru tanpa match DDS akan dibuat sebagai manual.';
            }

            $highestRow = $worksheet->getHighestDataRow();

            for ($row = self::DATA_START_ROW; $row <= $highestRow; $row++) {
                $rowData = $this->readRow($worksheet, $headerMap, $row);
                $summary['rows_read']++;

                if ($this->isEmptyDataRow($rowData)) {
                    $summary['rows_empty_skipped']++;

                    continue;
                }

                $ito = $this->normalizeKeyPart($rowData['ito_no'] ?? null);
                $item = $this->normalizeKeyPart($rowData['item_code'] ?? null);
                $unit = $this->normalizeUnitNo($rowData['unit_no'] ?? null);
                $matchKey = $this->matchKey($ito, $item, $unit);

                $manualFromExcel = $this->extractManualFields($rowData);
                $entry = $entries->get($matchKey);

                if ($entry !== null) {
                    $summary['matched']++;
                    $result = $this->applyToExistingEntry($entry, $manualFromExcel, $write);
                    if ($result['conflict']) {
                        $summary['conflicts']++;
                    }
                    if ($result['filled']) {
                        $summary['will_fill']++;
                        if ($write) {
                            $globalUpdates++;
                        }
                    }
                    if ($result['failure']) {
                        $summary['write_failures']++;
                    }

                    continue;
                }

                if ($isPratasaba) {
                    $summary['will_create_manual']++;
                    if ($write) {
                        $created = $this->createManualEntry($project->id, $ito, $item, $unit, $manualFromExcel);
                        if ($created) {
                            $globalInserts++;
                            $entries->put($matchKey, $created);
                        } else {
                            $summary['write_failures']++;
                        }
                    }

                    continue;
                }

                $sapRow = $sapKeyIndex->get($matchKey);
                if ($sapRow !== null) {
                    $summary['will_create_sap']++;
                    if ($write) {
                        $created = $this->createSapEntry($project->id, $sapRow, $manualFromExcel);
                        if ($created) {
                            $globalInserts++;
                            $entries->put($matchKey, $created);
                        } else {
                            $summary['write_failures']++;
                        }
                    }
                } else {
                    $summary['will_create_manual']++;
                    if ($sapUnavailable) {
                        $summary['notes'][] = 'Baris '.$row.': tidak dicocokkan ke SAP (koneksi tidak tersedia).';
                    } else {
                        $summary['notes'][] = 'Baris '.$row.': tidak dicocokkan ke SAP.';
                    }

                    if ($write) {
                        $created = $this->createManualEntry($project->id, $ito, $item, $unit, $manualFromExcel);
                        if ($created) {
                            $globalInserts++;
                            $entries->put($matchKey, $created);
                        } else {
                            $summary['write_failures']++;
                        }
                    }
                }
            }

            $sheetSummaries[$sheetName] = $summary;
        }

        return [
            'sheets' => $sheetSummaries,
            'total_inserts' => $globalInserts,
            'total_updates' => $globalUpdates,
            'dry_run' => ! $write,
        ];
    }

    /**
     * @return array{0: Collection<string, array<string, mixed>>, 1: bool}
     */
    private function loadSapIndex(): array
    {
        try {
            $from = Carbon::parse('2020-01-01');
            $to = Carbon::now()->addYear();
            $rows = $this->queryService->rows($from, $to);

            $index = collect();
            foreach ($rows as $row) {
                $key = $this->matchKey(
                    $this->normalizeKeyPart($row['ito_no'] ?? null),
                    $this->normalizeKeyPart($row['item_code'] ?? null),
                    $this->normalizeUnitNo($row['unit_no'] ?? null),
                );
                $index->put($key, $row);
            }

            return [$index, false];
        } catch (SapSqlQueryException|Throwable) {
            return [collect(), true];
        }
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $sapRowsByKey
     * @param  Collection<int, string>  $warehouseCodes
     * @return Collection<string, array<string, mixed>>
     */
    private function buildProjectSapKeyIndex(Collection $sapRowsByKey, Collection $warehouseCodes): Collection
    {
        $warehouseSet = $warehouseCodes->flip();
        $filtered = collect();

        foreach ($sapRowsByKey as $key => $row) {
            $toWarehouse = $row['to_warehouse'] ?? null;
            if ($toWarehouse !== null && $warehouseSet->has($toWarehouse)) {
                $filtered->put($key, $row);
            }
        }

        return $filtered;
    }

    /**
     * @return array<string, int>
     */
    private function buildHeaderMap(Worksheet $worksheet): array
    {
        $highestColumn = $worksheet->getHighestDataColumn();
        $highestIndex = Coordinate::columnIndexFromString($highestColumn);

        $row5 = $this->forwardFillRow($worksheet, self::HEADER_ROW_PRIMARY, $highestIndex);
        $row6 = $this->forwardFillRow($worksheet, self::HEADER_ROW_SECONDARY, $highestIndex);

        $map = [];

        for ($col = 1; $col <= $highestIndex; $col++) {
            $combined = $this->normalizeHeaderLabel(trim($row5[$col].' '.$row6[$col]));
            $single5 = $this->normalizeHeaderLabel($row5[$col]);
            $single6 = $this->normalizeHeaderLabel($row6[$col]);

            foreach (self::HEADER_ALIASES as $field => $aliases) {
                if ($this->labelMatches($combined, $aliases)
                    || $this->labelMatches($single5, $aliases)
                    || $this->labelMatches($single6, $aliases)) {
                    if (! isset($map[$field])) {
                        $map[$field] = $col;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * @return array<int, string>
     */
    private function forwardFillRow(Worksheet $worksheet, int $row, int $highestIndex): array
    {
        $values = [];
        $last = '';

        for ($col = 1; $col <= $highestIndex; $col++) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $cellValue = (string) $worksheet->getCell($letter.$row)->getCalculatedValue();
            $trimmed = trim(str_replace(["\n", "\r"], ' ', $cellValue));
            if ($trimmed !== '') {
                $last = $trimmed;
            }
            $values[$col] = $last;
        }

        return $values;
    }

    private function normalizeHeaderLabel(string $label): string
    {
        $label = preg_replace('/\s+/u', ' ', trim($label)) ?? '';

        return mb_strtoupper($label);
    }

    /**
     * @param  list<string>  $aliases
     */
    private function labelMatches(string $label, array $aliases): bool
    {
        if ($label === '') {
            return false;
        }

        foreach ($aliases as $alias) {
            if ($label === $alias || str_contains($label, $alias)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, int>  $headerMap
     * @return list<string>
     */
    private function missingRequiredHeaders(array $headerMap): array
    {
        $missing = [];
        foreach (self::REQUIRED_HEADER_KEYS as $key) {
            if (! isset($headerMap[$key])) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, int>  $headerMap
     * @return array<string, mixed>
     */
    private function readRow(Worksheet $worksheet, array $headerMap, int $row): array
    {
        $data = [];
        foreach ($headerMap as $field => $col) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $data[$field] = $worksheet->getCell($letter.$row)->getCalculatedValue();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $rowData
     */
    private function isEmptyDataRow(array $rowData): bool
    {
        $ito = trim((string) ($rowData['ito_no'] ?? ''));
        $item = trim((string) ($rowData['item_code'] ?? ''));
        $unit = trim((string) ($rowData['unit_no'] ?? ''));
        $spb = trim((string) ($rowData['no_spb'] ?? ''));

        return $ito === '' && $item === '' && $unit === '' && $spb === '';
    }

    /**
     * @param  array<string, mixed>  $rowData
     * @return array<string, mixed>
     */
    private function extractManualFields(array $rowData): array
    {
        $ekspedisiRaw = $rowData['ekspedisi'] ?? null;
        [$ekspedisi, $ekspedisiNote] = $this->normalizeEkspedisi($ekspedisiRaw);

        return [
            'no_spb' => $this->stringOrNull($rowData['no_spb'] ?? null),
            'remarks_barang' => $this->stringOrNull($rowData['remarks_barang'] ?? null),
            'tgl_delivery' => $this->parseDeliveryDate($rowData['tgl_delivery'] ?? null),
            'transporter' => $this->stringOrNull($rowData['transporter'] ?? null),
            'unit_kendaraan' => $this->stringOrNull($rowData['unit_kendaraan'] ?? null),
            'ekspedisi' => $ekspedisi,
            '_ekspedisi_note' => $ekspedisiNote,
        ];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function normalizeEkspedisi(mixed $value): array
    {
        $raw = $this->stringOrNull($value);
        if ($raw === null) {
            return [null, null];
        }

        $upper = mb_strtoupper($raw);
        if (str_contains($upper, 'NAMARA')) {
            return [DeliveryPartEntry::EKSPEDISI_OPTIONS[1], null];
        }
        if (str_contains($upper, 'JNE')) {
            return [DeliveryPartEntry::EKSPEDISI_OPTIONS[2], null];
        }
        if (str_contains($upper, 'TRUCK ARKA') || $upper === 'TRUCK ARKA') {
            return [DeliveryPartEntry::EKSPEDISI_OPTIONS[0], null];
        }

        return [null, 'Ekspedisi tidak dikenali: '.$raw];
    }

    private function parseDeliveryDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_numeric($value)) {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value))->startOfDay();
        }

        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})/', $string, $m)) {
            return Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay();
        }

        try {
            return Carbon::parse($string)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $manualFromExcel
     */
    /**
     * @param  array<string, mixed>  $manualFromExcel
     * @return array{filled: bool, conflict: bool, failure: bool}
     */
    private function applyToExistingEntry(DeliveryPartEntry $entry, array $manualFromExcel, bool $write): array
    {
        $hasConflict = false;
        $hasFill = false;
        $updates = [];

        foreach (self::MANUAL_FIELDS as $field) {
            $incoming = $manualFromExcel[$field] ?? null;
            if ($incoming === null || $incoming === '') {
                continue;
            }

            $current = $entry->{$field};
            if ($this->fieldIsFilled($field, $current)) {
                $hasConflict = true;

                continue;
            }

            $updates[$field] = $incoming;
            $hasFill = true;
        }

        if (! $hasFill) {
            return [
                'filled' => false,
                'conflict' => $hasConflict,
                'failure' => false,
            ];
        }

        if (! $write) {
            return [
                'filled' => true,
                'conflict' => $hasConflict,
                'failure' => false,
            ];
        }

        foreach ($updates as $field => $value) {
            $entry->{$field} = $value;
        }

        try {
            $entry->save();

            return [
                'filled' => true,
                'conflict' => $hasConflict,
                'failure' => false,
            ];
        } catch (Throwable) {
            return [
                'filled' => false,
                'conflict' => $hasConflict,
                'failure' => true,
            ];
        }
    }

    private function fieldIsFilled(string $field, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $manualFromExcel
     */
    private function createSapEntry(int $projectId, array $sapRow, array $manualFromExcel): ?DeliveryPartEntry
    {
        try {
            return DeliveryPartEntry::query()->create([
                'project_id' => $projectId,
                'ito_no' => $sapRow['ito_no'] ?? null,
                'item_code' => $sapRow['item_code'] ?? null,
                'unit_no' => $sapRow['unit_no'] ?? null,
                'source' => DeliveryPartEntry::SOURCE_SAP,
                'no_spb' => $manualFromExcel['no_spb'] ?? null,
                'remarks_barang' => $manualFromExcel['remarks_barang'] ?? null,
                'tgl_delivery' => $manualFromExcel['tgl_delivery'] ?? null,
                'transporter' => $manualFromExcel['transporter'] ?? null,
                'unit_kendaraan' => $manualFromExcel['unit_kendaraan'] ?? null,
                'ekspedisi' => $manualFromExcel['ekspedisi'] ?? null,
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $manualFromExcel
     */
    private function createManualEntry(
        int $projectId,
        string $ito,
        string $item,
        string $unit,
        array $manualFromExcel,
    ): ?DeliveryPartEntry {
        try {
            return DeliveryPartEntry::query()->create([
                'project_id' => $projectId,
                'ito_no' => $ito !== '' ? $ito : null,
                'item_code' => $item !== '' ? $item : null,
                'unit_no' => $unit !== '' ? $unit : null,
                'source' => DeliveryPartEntry::SOURCE_MANUAL,
                'no_spb' => $manualFromExcel['no_spb'] ?? null,
                'remarks_barang' => $manualFromExcel['remarks_barang'] ?? null,
                'tgl_delivery' => $manualFromExcel['tgl_delivery'] ?? null,
                'transporter' => $manualFromExcel['transporter'] ?? null,
                'unit_kendaraan' => $manualFromExcel['unit_kendaraan'] ?? null,
                'ekspedisi' => $manualFromExcel['ekspedisi'] ?? null,
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeKeyPart(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return mb_strtoupper(trim((string) $value));
    }

    private function normalizeUnitNo(mixed $value): string
    {
        $normalized = $this->normalizeKeyPart(is_scalar($value) || $value === null ? (string) $value : '');
        if ($normalized === 'NON_UNIT' || $normalized === 'NON UNIT') {
            return '';
        }

        return $normalized;
    }

    private function matchKey(string $ito, string $item, string $unit): string
    {
        return implode("\0", [$ito, $item, $unit]);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
