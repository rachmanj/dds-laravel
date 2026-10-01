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
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class DeliveryPartExcelImportService
{
    public const SHEETS = ['017C', '022C', '026C', 'PRATASABA'];

    public const SAP_IMPORT_CHUNK_DAYS = 31;

    private const SAP_IMPORT_FALLBACK_FROM = '2020-01-01';

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
     * Kolom data tetap B..S (kolom A kosong). Pembacaan baris memakai posisi ini, bukan teks header.
     *
     * @var array<string, int>
     */
    private const COLUMN_POSITION_MAP = [
        'tanggal_received' => 2,
        'supplier' => 3,
        'po_number' => 4,
        'no_spb' => 5,
        'ito_no' => 6,
        'unit_no' => 7,
        'item_code' => 8,
        'description' => 9,
        'qty' => 10,
        'uom' => 11,
        'remarks_barang' => 12,
        'tgl_delivery' => 13,
        'transporter' => 14,
        'unit_kendaraan' => 15,
        'ekspedisi' => 16,
        'tgl_iti' => 17,
        'no_iti' => 18,
        'keterangan' => 19,
    ];

    /**
     * Label header yang diharapkan (baris 5/6 digabung) — hanya untuk catatan kewajaran.
     *
     * @var array<string, list<string>>
     */
    private const EXPECTED_HEADER_ALIASES = [
        'tanggal_received' => ['TANGGAL RECEIVED', 'TGL RECEIVED'],
        'supplier' => ['SUPPLIER'],
        'po_number' => ['PO NUMBER', 'PO NO'],
        'no_spb' => ['NO. SPB', 'NO SPB'],
        'ito_no' => ['NO ITO', 'NO. ITO', 'ITO NO'],
        'unit_no' => ['NO UNIT', 'NO. UNIT', 'UNIT NO'],
        'item_code' => ['PARTS NUMBER', 'PART NUMBER', 'PARTS NO', 'ITEM CODE'],
        'description' => ['DESCRIPTIONS', 'DESCRIPTION', 'DESC'],
        'qty' => ['QTY', 'QUANTITY'],
        'uom' => ['UOM'],
        'remarks_barang' => ['REMARKS BARANG', 'REMARK BARANG', 'REMARKS', 'REMAKS'],
        'tgl_delivery' => ['TGL DELIVERY', 'TANGGAL DELIVERY', 'DEIVERY', 'DELIVERY'],
        'transporter' => ['TRANSPORTER'],
        'unit_kendaraan' => ['UNIT & NO KENDARAAN', 'UNIT AND NO KENDARAAN', 'UNIT NO KENDARAAN'],
        'ekspedisi' => ['EKSPEDISI', 'EKSPEDISI PENGIRIM', 'EXPEDISI', 'EXPEDISI PENGIRIM'],
        'tgl_iti' => ['TGL ITI', 'TANGGAL ITI'],
        'no_iti' => ['NO. ITI', 'NO ITI'],
        'keterangan' => ['KETERANGAN'],
    ];

    public function __construct(
        private DeliveryPartQueryService $queryService,
    ) {}

    /**
     * @param  null|callable(string, array<string, mixed>): void  $onSheetComplete
     * @return array<string, mixed>
     */
    public function import(string $filePath, bool $write, ?callable $onSheetComplete = null): array
    {
        $spreadsheet = $this->loadSpreadsheet($filePath);
        $globalInserts = 0;
        $globalUpdates = 0;
        $sheetSummaries = [];

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

            $highestRow = $worksheet->getHighestDataRow();
            if ($highestRow < self::DATA_START_ROW) {
                $sheetSummaries[$sheetName] = [
                    'skipped' => true,
                    'skip_reason' => 'Sheet tidak memiliki baris data.',
                ];

                continue;
            }

            $columnMap = self::COLUMN_POSITION_MAP;

            $isPratasaba = $sheetName === 'PRATASABA';

            $warehouseCodes = $isPratasaba
                ? collect()
                : LogisticsWarehouseProject::query()
                    ->where('project_id', $project->id)
                    ->where('is_active', true)
                    ->pluck('whs_code');

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
                'sap_chunks_loaded' => 0,
            ];

            foreach ($this->headerSanityNotes($worksheet) as $headerNote) {
                $summary['notes'][] = $headerNote;
            }

            /** @var list<array{row: int, ito: string, item: string, unit: string, match_key: string, manual: array<string, mixed>, received_date: ?Carbon}> $pendingSapRows */
            $pendingSapRows = [];

            for ($row = self::DATA_START_ROW; $row <= $highestRow; $row++) {
                $rowData = $this->readRow($worksheet, $columnMap, $row);
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
                foreach ($manualFromExcel['_row_notes'] ?? [] as $note) {
                    $summary['notes'][] = 'Baris '.$row.': '.$note;
                }
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

                $pendingSapRows[] = [
                    'row' => $row,
                    'ito' => $ito,
                    'item' => $item,
                    'unit' => $unit,
                    'match_key' => $matchKey,
                    'manual' => $manualFromExcel,
                    'received_date' => $this->parseReceivedDate($rowData['tanggal_received'] ?? null),
                ];
            }

            if ($pendingSapRows !== []) {
                [$sapMatches, $sapUnavailable, $chunksLoaded] = $this->loadSapMatchesForPendingRows(
                    $pendingSapRows,
                    $warehouseCodes,
                );
                $summary['sap_chunks_loaded'] = $chunksLoaded;

                if ($sapUnavailable) {
                    $summary['notes'][] = 'Koneksi SAP tidak tersedia; baris baru tanpa match DDS akan dibuat sebagai manual.';
                }

                foreach ($pendingSapRows as $pending) {
                    $row = $pending['row'];
                    $matchKey = $pending['match_key'];
                    $manualFromExcel = $pending['manual'];
                    $ito = $pending['ito'];
                    $item = $pending['item'];
                    $unit = $pending['unit'];

                    $sapRow = $sapMatches[$matchKey] ?? null;
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
            }

            $sheetSummaries[$sheetName] = $summary;

            if ($onSheetComplete !== null) {
                $onSheetComplete($sheetName, $summary);
            }
        }

        return [
            'sheets' => $sheetSummaries,
            'total_inserts' => $globalInserts,
            'total_updates' => $globalUpdates,
            'dry_run' => ! $write,
            'peak_memory_bytes' => memory_get_peak_usage(true),
        ];
    }

    private function loadSpreadsheet(string $filePath): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        // PhpSpreadsheet may emit E_WARNING on empty XML parts (common in Google Sheets exports);
        // Laravel promotes warnings to exceptions — mask warnings only for this load.
        $previous = error_reporting();
        error_reporting($previous & ~E_WARNING);

        try {
            return IOFactory::load($filePath);
        } finally {
            error_reporting($previous);
        }
    }

    /**
     * @param  list<array{row: int, ito: string, item: string, unit: string, match_key: string, manual: array<string, mixed>, received_date: ?Carbon}>  $pendingSapRows
     * @param  Collection<int, string>  $warehouseCodes
     * @return array{0: array<string, array<string, mixed>>, 1: bool, 2: int}
     */
    private function loadSapMatchesForPendingRows(array $pendingSapRows, Collection $warehouseCodes): array
    {
        [$minDate, $maxDate] = $this->resolveSapDateBoundsFromPendingRows($pendingSapRows);
        $chunks = $this->sapDateChunks($minDate, $maxDate);
        $sapMatches = [];
        $sapUnavailable = false;
        $chunksLoaded = 0;
        $warehouseSet = $warehouseCodes->flip();

        foreach ($chunks as $chunkIndex => [$chunkFrom, $chunkTo]) {
            try {
                $rows = $this->queryService->fetchRowsWithoutCache($chunkFrom, $chunkTo);
            } catch (SapSqlQueryException $e) {
                if ($chunkIndex === 0) {
                    $sapUnavailable = true;

                    return [[], true, 0];
                }

                throw SapSqlQueryException::connectionFailed(
                    'Gagal membaca data SAP untuk periode '.$chunkFrom->toDateString()
                    .' s/d '.$chunkTo->toDateString().': '.$e->getMessage()
                );
            }

            $chunksLoaded++;

            $chunkIndexByKey = [];
            foreach ($rows as $row) {
                $toWarehouse = $row['to_warehouse'] ?? null;
                if ($toWarehouse === null || ! $warehouseSet->has($toWarehouse)) {
                    continue;
                }

                $key = $this->matchKey(
                    $this->normalizeKeyPart($row['ito_no'] ?? null),
                    $this->normalizeKeyPart($row['item_code'] ?? null),
                    $this->normalizeUnitNo($row['unit_no'] ?? null),
                );
                $chunkIndexByKey[$key] = $row;
            }

            unset($rows);

            foreach ($pendingSapRows as $pending) {
                $receivedDate = $pending['received_date'];
                if ($receivedDate !== null && ($receivedDate->lt($chunkFrom) || $receivedDate->gt($chunkTo))) {
                    continue;
                }

                $matchKey = $pending['match_key'];
                if (isset($chunkIndexByKey[$matchKey])) {
                    $sapMatches[$matchKey] = $chunkIndexByKey[$matchKey];
                }
            }

            unset($chunkIndexByKey);
        }

        return [$sapMatches, $sapUnavailable, $chunksLoaded];
    }

    /**
     * @param  list<array{row: int, ito: string, item: string, unit: string, match_key: string, manual: array<string, mixed>, received_date: ?Carbon}>  $pendingSapRows
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveSapDateBoundsFromPendingRows(array $pendingSapRows): array
    {
        $min = null;
        $max = null;

        foreach ($pendingSapRows as $pending) {
            $date = $pending['received_date'];
            if ($date === null) {
                continue;
            }

            if ($min === null || $date->lt($min)) {
                $min = $date->copy();
            }
            if ($max === null || $date->gt($max)) {
                $max = $date->copy();
            }
        }

        if ($min === null || $max === null) {
            return [
                Carbon::parse(self::SAP_IMPORT_FALLBACK_FROM)->startOfDay(),
                Carbon::now()->addYear()->endOfDay(),
            ];
        }

        return [$min->copy()->startOfDay(), $max->copy()->startOfDay()];
    }

    /**
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    private function sapDateChunks(Carbon $minDate, Carbon $maxDate, int $chunkDays = self::SAP_IMPORT_CHUNK_DAYS): array
    {
        $chunks = [];
        $cursor = $minDate->copy()->startOfDay();
        $end = $maxDate->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $chunkEnd = $cursor->copy()->addDays($chunkDays - 1);
            if ($chunkEnd->gt($end)) {
                $chunkEnd = $end->copy();
            }

            $chunks[] = [$cursor->copy(), $chunkEnd->copy()];
            $cursor = $chunkEnd->copy()->addDay();
        }

        return $chunks;
    }

    private function parseReceivedDate(mixed $value): ?Carbon
    {
        [$date, $note] = $this->parseDeliveryDate($value);
        if ($note !== null) {
            return null;
        }

        return $date;
    }

    /**
     * @return list<string>
     */
    private function headerSanityNotes(Worksheet $worksheet): array
    {
        $highestColumn = $worksheet->getHighestDataColumn();
        $highestIndex = max(
            Coordinate::columnIndexFromString($highestColumn),
            max(self::COLUMN_POSITION_MAP),
        );

        $row5 = $this->forwardFillRow($worksheet, self::HEADER_ROW_PRIMARY, $highestIndex);
        $row6 = $this->forwardFillRow($worksheet, self::HEADER_ROW_SECONDARY, $highestIndex);

        $notes = [];

        foreach (self::COLUMN_POSITION_MAP as $field => $col) {
            $combined = $this->normalizeHeaderLabel(trim(($row5[$col] ?? '').' '.($row6[$col] ?? '')));
            $single5 = $this->normalizeHeaderLabel($row5[$col] ?? '');
            $single6 = $this->normalizeHeaderLabel($row6[$col] ?? '');
            $aliases = self::EXPECTED_HEADER_ALIASES[$field] ?? [];

            if ($combined === '' && $single5 === '' && $single6 === '') {
                $notes[] = 'Header kolom '.Coordinate::stringFromColumnIndex($col).' ('.$field.') kosong; data tetap dibaca dari posisi kolom.';

                continue;
            }

            if ($this->labelMatches($combined, $aliases)
                || $this->labelMatches($single5, $aliases)
                || $this->labelMatches($single6, $aliases)) {
                continue;
            }

            $labelShown = trim($row5[$col].' '.$row6[$col]);
            $notes[] = 'Header kolom '.Coordinate::stringFromColumnIndex($col).' tidak dikenali ('.$labelShown.'); data tetap dibaca dari posisi kolom.';
        }

        return $notes;
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
     * @param  array<string, int>  $columnMap
     * @return array<string, mixed>
     */
    private function readRow(Worksheet $worksheet, array $columnMap, int $row): array
    {
        $data = [];
        $importNotes = [];

        foreach ($columnMap as $field => $col) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $raw = $worksheet->getCell($letter.$row)->getCalculatedValue();
            [$sanitized, $note] = $this->sanitizeImportedCellValue($raw, $field);
            $data[$field] = $sanitized;
            if ($note !== null) {
                $importNotes[] = $note;
            }
        }

        if ($importNotes !== []) {
            $data['_import_notes'] = $importNotes;
        }

        return $data;
    }

    /**
     * @return array{0: mixed, 1: ?string}
     */
    private function sanitizeImportedCellValue(mixed $value, string $field): array
    {
        if ($value === null || $value === '') {
            return [$value, null];
        }

        if (is_string($value) && str_starts_with(ltrim($value), '=')) {
            return [null, 'Kolom '.$field.' berisi rumus, dianggap kosong.'];
        }

        return [$value, null];
    }

    /**
     * @param  array<string, mixed>  $rowData
     */
    private function isEmptyDataRow(array $rowData): bool
    {
        $ito = trim((string) ($rowData['ito_no'] ?? ''));
        $item = trim((string) ($rowData['item_code'] ?? ''));
        $description = trim((string) ($rowData['description'] ?? ''));
        if (is_string($rowData['ito_no'] ?? null) && str_starts_with(ltrim((string) $rowData['ito_no']), '=')) {
            $ito = '';
        }
        if (is_string($rowData['item_code'] ?? null) && str_starts_with(ltrim((string) $rowData['item_code']), '=')) {
            $item = '';
        }
        if (is_string($rowData['description'] ?? null) && str_starts_with(ltrim((string) $rowData['description']), '=')) {
            $description = '';
        }

        return $ito === '' && $item === '' && $description === '';
    }

    /**
     * @param  array<string, mixed>  $rowData
     * @return array<string, mixed>
     */
    private function extractManualFields(array $rowData): array
    {
        $rowNotes = $rowData['_import_notes'] ?? [];

        $ekspedisiRaw = $rowData['ekspedisi'] ?? null;
        [$ekspedisi, $ekspedisiNote] = $this->normalizeEkspedisi($ekspedisiRaw);
        if ($ekspedisiNote !== null) {
            $rowNotes[] = $ekspedisiNote;
        }

        [$tglDelivery, $dateNote] = $this->parseDeliveryDate($rowData['tgl_delivery'] ?? null);
        if ($dateNote !== null) {
            $rowNotes[] = $dateNote;
        }

        return [
            'no_spb' => $this->stringOrNull($rowData['no_spb'] ?? null),
            'remarks_barang' => $this->stringOrNull($rowData['remarks_barang'] ?? null),
            'tgl_delivery' => $tglDelivery,
            'transporter' => $this->stringOrNull($rowData['transporter'] ?? null),
            'unit_kendaraan' => $this->stringOrNull($rowData['unit_kendaraan'] ?? null),
            'ekspedisi' => $ekspedisi,
            '_row_notes' => $rowNotes,
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

    /**
     * @return array{0: ?Carbon, 1: ?string}
     */
    private function parseDeliveryDate(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [null, null];
        }

        if (is_string($value) && str_starts_with(ltrim($value), '=')) {
            return [null, null];
        }

        if ($value instanceof \DateTimeInterface) {
            return [
                Carbon::instance($value)->timezone(config('app.timezone'))->startOfDay(),
                null,
            ];
        }

        if (is_numeric($value)) {
            $serial = (float) $value;
            if ($serial <= 0) {
                return [null, 'Tgl Delivery tidak dapat dikonversi: '.$value];
            }

            return [
                Carbon::instance(ExcelDate::excelToDateTimeObject($serial))
                    ->timezone(config('app.timezone'))
                    ->startOfDay(),
                null,
            ];
        }

        $string = trim((string) $value);
        if ($string === '') {
            return [null, null];
        }

        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})/', $string, $m)) {
            return [
                Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay(),
                null,
            ];
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $string, $m)) {
            return [
                Carbon::createFromDate((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay(),
                null,
            ];
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $string)) {
            try {
                return [Carbon::parse($string)->timezone(config('app.timezone'))->startOfDay(), null];
            } catch (Throwable) {
                return [null, 'Tgl Delivery tidak dapat dikonversi: '.$string];
            }
        }

        try {
            return [Carbon::parse($string)->timezone(config('app.timezone'))->startOfDay(), null];
        } catch (Throwable) {
            return [null, 'Tgl Delivery tidak dapat dikonversi: '.$string];
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
        if ($string === '' || str_starts_with($string, '=')) {
            return null;
        }

        return $string;
    }
}
