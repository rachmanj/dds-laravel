<?php

namespace Tests\Feature;

use App\Models\DeliveryPartEntry;
use App\Models\LogisticsWarehouseProject;
use App\Models\Project;
use App\Services\Logistics\DeliveryPartExcelImportService;
use App\Services\Logistics\DeliveryPartQueryService;
use Carbon\Carbon;
use Database\Seeders\DeliveryPartPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class DeliveryPartImportExcelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(DeliveryPartPermissionSeeder::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createProject(string $code, string $whs): Project
    {
        $project = Project::query()->create([
            'code' => $code,
            'owner' => 'Test',
            'location' => 'Test',
            'is_active' => true,
        ]);

        LogisticsWarehouseProject::query()->create([
            'whs_code' => $whs,
            'project_id' => $project->id,
            'is_active' => true,
        ]);

        return $project;
    }

    /**
     * @param  array<string, list<string>>  $headerOverrides  Kolom B..S baris 5 (opsional)
     * @param  array<int, array<string, mixed>>  $dataRows
     */
    private function buildSampleExcel(
        string $path,
        array $dataRows,
        array $headerOverrides = [],
        string $sheetTitle = '017C',
    ): void {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetTitle);

        $defaultHeaders = [
            'B' => 'TANGGAL RECEIVED',
            'C' => 'Supplier',
            'D' => 'PO Number',
            'E' => 'No. SPB',
            'F' => 'NO ITO',
            'G' => 'No Unit',
            'H' => 'Parts Number',
            'I' => 'Descriptions',
            'J' => 'QTY',
            'K' => 'UOM',
            'L' => 'Remarks Barang',
            'M' => 'Tgl Delivery',
            'N' => 'Transporter',
            'O' => 'Unit & No Kendaraan',
            'P' => 'Ekspedisi',
            'Q' => 'Tgl ITI',
            'R' => 'NO. ITI',
            'S' => 'Keterangan',
        ];

        $headers = array_merge($defaultHeaders, $headerOverrides);
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col.'5', $label);
        }

        $rowNum = 7;
        foreach ($dataRows as $row) {
            $sheet->setCellValue('E'.$rowNum, $row['no_spb'] ?? '');
            $sheet->setCellValue('F'.$rowNum, $row['ito_no'] ?? '');
            $sheet->setCellValue('G'.$rowNum, $row['unit_no'] ?? '');
            $sheet->setCellValue('H'.$rowNum, $row['item_code'] ?? '');
            $sheet->setCellValue('I'.$rowNum, $row['description'] ?? ($row['item_code'] ?? 'Deskripsi'));
            $sheet->setCellValue('L'.$rowNum, $row['remarks_barang'] ?? '');
            $sheet->setCellValue('M'.$rowNum, $row['tgl_delivery'] ?? '');
            $sheet->setCellValue('N'.$rowNum, $row['transporter'] ?? '');
            $sheet->setCellValue('O'.$rowNum, $row['unit_kendaraan'] ?? '');
            if (array_key_exists('ekspedisi', $row)) {
                $sheet->setCellValue('P'.$rowNum, $row['ekspedisi']);
            }
            $rowNum++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }

    private function bindEmptySap(): void
    {
        $fake = Mockery::mock(DeliveryPartQueryService::class);
        $fake->shouldReceive('fetchRowsWithoutCache')->andReturn(collect());
        $this->app->instance(DeliveryPartQueryService::class, $fake);
    }

    public function test_command_requires_file_option(): void
    {
        $this->artisan('delivery-part:import-excel')
            ->assertExitCode(1);
    }

    public function test_dry_run_does_not_write(): void
    {
        $this->createProject('017C', '02-SPT');
        $this->bindEmptySap();

        $path = storage_path('app/testing-delivery-part-import.xlsx');
        $this->buildSampleExcel($path, [
            [
                'ito_no' => 'ITO-9',
                'item_code' => 'PART-X',
                'unit_no' => 'U1',
                'no_spb' => 'SPB-DRY',
                'remarks_barang' => 'Catatan',
            ],
        ]);

        Artisan::call('delivery-part:import-excel', ['--file' => $path]);
        $output = Artisan::output();
        $this->assertStringContainsString('dry-run', strtolower($output));
        $this->assertDatabaseCount('delivery_part_entries', 0);

        @unlink($path);
    }

    public function test_write_fills_empty_manual_fields_and_respects_conflict(): void
    {
        $project = $this->createProject('017C', '02-SPT');

        DeliveryPartEntry::query()->create([
            'project_id' => $project->id,
            'ito_no' => 'ITO-1',
            'item_code' => 'PART-A',
            'unit_no' => 'U-10',
            'source' => DeliveryPartEntry::SOURCE_SAP,
            'no_spb' => null,
        ]);

        DeliveryPartEntry::query()->create([
            'project_id' => $project->id,
            'ito_no' => 'ITO-2',
            'item_code' => 'PART-B',
            'unit_no' => '',
            'source' => DeliveryPartEntry::SOURCE_SAP,
            'no_spb' => 'SUDAH-ADA',
        ]);

        $this->bindEmptySap();

        $path = storage_path('app/testing-delivery-part-import-write.xlsx');
        $this->buildSampleExcel($path, [
            [
                'ito_no' => 'ITO-1',
                'item_code' => 'PART-A',
                'unit_no' => 'U-10',
                'no_spb' => 'SPB-FILL',
                'transporter' => 'PT Kirim',
            ],
            [
                'ito_no' => 'ITO-2',
                'item_code' => 'PART-B',
                'unit_no' => 'NON_UNIT',
                'no_spb' => 'SPB-BARU',
            ],
            [
                'ito_no' => 'ITO-99',
                'item_code' => 'PART-Z',
                'unit_no' => '',
                'no_spb' => 'SPB-MANUAL',
                'ekspedisi' => 'NAMARA JAYA',
            ],
        ]);

        $this->artisan('delivery-part:import-excel', ['--file' => $path, '--write' => true])
            ->assertExitCode(0);

        $filled = DeliveryPartEntry::query()->where('ito_no', 'ITO-1')->first();
        $this->assertNotNull($filled);
        $this->assertSame('SPB-FILL', $filled->no_spb);
        $this->assertSame('PT Kirim', $filled->transporter);

        $conflict = DeliveryPartEntry::query()->where('ito_no', 'ITO-2')->first();
        $this->assertSame('SUDAH-ADA', $conflict->no_spb);

        $manual = DeliveryPartEntry::query()->where('ito_no', 'ITO-99')->first();
        $this->assertNotNull($manual);
        $this->assertSame(DeliveryPartEntry::SOURCE_MANUAL, $manual->source);
        $this->assertSame('EKSPEDISI NAMARA', $manual->ekspedisi);

        @unlink($path);
    }

    public function test_sheet_with_misspelled_headers_still_imports_columns_by_position(): void
    {
        $project = $this->createProject('022C', '08-SPT');
        $this->bindEmptySap();

        $path = storage_path('app/testing-delivery-part-typo-headers.xlsx');
        $this->buildSampleExcel($path, [
            [
                'ito_no' => '251008078',
                'item_code' => 'CE-BUCKET150',
                'unit_no' => 'NON_UNIT',
                'tgl_delivery' => 46024,
                'transporter' => 'RUSEP',
                'unit_kendaraan' => 'TRUCK PS NAMARA',
                'ekspedisi' => 'EKSPEDISI NAMARA',
            ],
        ], [
            'M' => 'Tgl Deivery',
            'L' => 'Remaks Barang',
            'P' => 'Expedisi',
        ], '022C');

        $this->artisan('delivery-part:import-excel', ['--file' => $path, '--write' => true])
            ->assertExitCode(0)
            ->doesntExpectOutputToContain('Kolom wajib');

        $entry = DeliveryPartEntry::query()->where('project_id', $project->id)->where('ito_no', '251008078')->first();
        $this->assertNotNull($entry);
        $this->assertSame('CE-BUCKET150', $entry->item_code);
        $this->assertSame('EKSPEDISI NAMARA', $entry->ekspedisi);
        $this->assertSame('2026-01-02', $entry->tgl_delivery?->toDateString());

        @unlink($path);
    }

    public function test_sheet_without_ekspedisi_column_data_still_imports_with_null_ekspedisi(): void
    {
        $project = $this->createProject('017C', '02-SPT');
        $this->bindEmptySap();

        $path = storage_path('app/testing-delivery-part-no-ekspedisi.xlsx');
        $this->buildSampleExcel($path, [
            [
                'ito_no' => 'ITO-NO-EKS',
                'item_code' => 'PART-E',
                'unit_no' => 'U1',
                'no_spb' => 'SPB-E',
                'ekspedisi' => '',
            ],
        ], [
            'P' => 'Kolom Lain',
        ]);

        Artisan::call('delivery-part:import-excel', ['--file' => $path, '--write' => true]);
        $output = Artisan::output();
        $this->assertStringNotContainsString('Kolom wajib', $output);

        $entry = DeliveryPartEntry::query()->where('project_id', $project->id)->where('ito_no', 'ITO-NO-EKS')->first();
        $this->assertNotNull($entry);
        $this->assertNull($entry->ekspedisi);

        @unlink($path);
    }

    public function test_row_without_ito_parts_or_description_is_skipped_as_empty(): void
    {
        $this->createProject('017C', '02-SPT');
        $this->bindEmptySap();

        $path = storage_path('app/testing-delivery-part-empty-row.xlsx');
        $this->buildSampleExcel($path, [
            [
                'ito_no' => '',
                'item_code' => '',
                'unit_no' => 'U-ONLY',
                'no_spb' => 'SPB-ONLY',
                'description' => '',
            ],
            [
                'ito_no' => 'ITO-OK',
                'item_code' => 'PART-OK',
                'unit_no' => 'U1',
                'no_spb' => 'SPB-OK',
            ],
        ]);

        $this->artisan('delivery-part:import-excel', ['--file' => $path, '--write' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('Baris kosong dilewati: 1');

        $this->assertDatabaseCount('delivery_part_entries', 1);
        $this->assertNotNull(DeliveryPartEntry::query()->where('ito_no', 'ITO-OK')->first());

        @unlink($path);
    }

    public function test_sap_match_creates_sap_source_entry(): void
    {
        $this->createProject('017C', '02-SPT');

        $sapRow = [
            'grpo_no' => 'G1',
            'ito_no' => 'ITO-SAP',
            'ito_date' => Carbon::parse('2026-01-05'),
            'ito_created_date' => Carbon::parse('2026-01-05'),
            'iti_no' => null,
            'iti_date' => null,
            'item_code' => 'SAP-PART',
            'description' => 'Desc',
            'uom' => 'PCS',
            'qty' => 1.0,
            'po_no' => 'PO',
            'pr_no' => null,
            'mr_no' => null,
            'unit_no' => 'UNIT-1',
            'vendor' => 'V',
            'from_warehouse' => '01',
            'to_warehouse' => '02-SPT',
            'delivery_status' => 'Not Delivered',
            'delivery_date' => null,
            'remarks' => null,
        ];

        $fake = Mockery::mock(DeliveryPartQueryService::class);
        $fake->shouldReceive('fetchRowsWithoutCache')->andReturn(Collection::make([$sapRow]));
        $this->app->instance(DeliveryPartQueryService::class, $fake);

        $path = storage_path('app/testing-delivery-part-sap.xlsx');
        $this->buildSampleExcel($path, [
            [
                'ito_no' => 'ITO-SAP',
                'item_code' => 'SAP-PART',
                'unit_no' => 'UNIT-1',
                'no_spb' => 'SPB-SAP',
            ],
        ]);

        $this->artisan('delivery-part:import-excel', ['--file' => $path, '--write' => true])
            ->assertExitCode(0);

        $entry = DeliveryPartEntry::query()->where('ito_no', 'ITO-SAP')->first();
        $this->assertNotNull($entry);
        $this->assertSame(DeliveryPartEntry::SOURCE_SAP, $entry->source);
        $this->assertSame('SPB-SAP', $entry->no_spb);

        @unlink($path);
    }

    public function test_excel_serial_date_converts_to_calendar_date(): void
    {
        $project = $this->createProject('017C', '02-SPT');
        $this->bindEmptySap();

        $path = storage_path('app/testing-delivery-part-serial-date.xlsx');
        $this->buildSampleExcel($path, [
            [
                'ito_no' => 'ITO-DATE',
                'item_code' => 'PART-D',
                'unit_no' => 'U1',
                'no_spb' => 'SPB-D',
                'tgl_delivery' => 46031,
            ],
        ]);

        $this->artisan('delivery-part:import-excel', ['--file' => $path, '--write' => true])
            ->assertExitCode(0);

        $entry = DeliveryPartEntry::query()->where('ito_no', 'ITO-DATE')->first();
        $this->assertNotNull($entry);
        $this->assertSame('2026-01-09', $entry->tgl_delivery?->toDateString());

        @unlink($path);
    }

    public function test_formula_cell_is_treated_as_empty(): void
    {
        $project = $this->createProject('017C', '02-SPT');

        DeliveryPartEntry::query()->create([
            'project_id' => $project->id,
            'ito_no' => 'ITO-FORM',
            'item_code' => 'PART-F',
            'unit_no' => 'U1',
            'source' => DeliveryPartEntry::SOURCE_SAP,
            'no_spb' => null,
            'remarks_barang' => 'Sudah ada',
        ]);

        $this->bindEmptySap();

        $path = storage_path('app/testing-delivery-part-formula.xlsx');
        $this->buildSampleExcel($path, [
            [
                'ito_no' => 'ITO-FORM',
                'item_code' => 'PART-F',
                'unit_no' => 'U1',
                'no_spb' => '=IF(OR(R7="",R7=0),"",R7)',
                'remarks_barang' => '=IF(1=1,"formula","x")',
                'description' => 'Deskripsi',
            ],
        ]);

        $this->artisan('delivery-part:import-excel', ['--file' => $path, '--write' => true])
            ->assertExitCode(0);

        $entry = DeliveryPartEntry::query()->where('ito_no', 'ITO-FORM')->first();
        $this->assertNotNull($entry);
        $this->assertNull($entry->no_spb);
        $this->assertSame('Sudah ada', $entry->remarks_barang);

        @unlink($path);
    }

    public function test_import_splits_sap_fetch_into_multiple_chunks_for_wide_received_dates(): void
    {
        $this->createProject('017C', '02-SPT');

        $fake = Mockery::mock(DeliveryPartQueryService::class);
        $fake->shouldReceive('fetchRowsWithoutCache')
            ->times(3)
            ->andReturn(collect());
        $this->app->instance(DeliveryPartQueryService::class, $fake);

        $path = storage_path('app/testing-delivery-part-chunks.xlsx');
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('017C');
        $sheet->setCellValue('B5', 'TANGGAL RECEIVED');
        $sheet->setCellValue('F5', 'NO ITO');
        $sheet->setCellValue('H5', 'Parts Number');
        $sheet->setCellValue('B7', '2026-01-01');
        $sheet->setCellValue('F7', 'ITO-CHUNK');
        $sheet->setCellValue('H7', 'PART-C');
        $sheet->setCellValue('I7', 'Deskripsi');
        $sheet->setCellValue('B8', '2026-03-15');
        $sheet->setCellValue('F8', 'ITO-CHUNK-2');
        $sheet->setCellValue('H8', 'PART-D');
        $sheet->setCellValue('I8', 'Deskripsi');
        (new Xlsx($spreadsheet))->save($path);

        $service = $this->app->make(DeliveryPartExcelImportService::class);
        $result = $service->import($path, false);

        $this->assertSame(3, $result['sheets']['017C']['sap_chunks_loaded'] ?? 0);

        @unlink($path);
    }

    public function test_import_aborts_when_sap_chunk_fails_after_first_successful_chunk(): void
    {
        $this->createProject('017C', '02-SPT');

        $fake = Mockery::mock(DeliveryPartQueryService::class);
        $fake->shouldReceive('fetchRowsWithoutCache')
            ->once()
            ->andReturn(collect());
        $fake->shouldReceive('fetchRowsWithoutCache')
            ->once()
            ->andThrow(new \App\Exceptions\SapSqlQueryException('SAP timeout'));
        $this->app->instance(DeliveryPartQueryService::class, $fake);

        $path = storage_path('app/testing-delivery-part-chunk-fail.xlsx');
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('017C');
        $sheet->setCellValue('B5', 'TANGGAL RECEIVED');
        $sheet->setCellValue('F5', 'NO ITO');
        $sheet->setCellValue('H5', 'Parts Number');
        $sheet->setCellValue('B7', '2026-01-01');
        $sheet->setCellValue('F7', 'ITO-FAIL');
        $sheet->setCellValue('H7', 'PART-F');
        $sheet->setCellValue('I7', 'Deskripsi');
        $sheet->setCellValue('B8', '2026-03-15');
        $sheet->setCellValue('F8', 'ITO-FAIL-2');
        $sheet->setCellValue('H8', 'PART-G');
        $sheet->setCellValue('I8', 'Deskripsi');
        (new Xlsx($spreadsheet))->save($path);

        $service = $this->app->make(DeliveryPartExcelImportService::class);

        $this->expectException(\App\Exceptions\SapSqlQueryException::class);
        $this->expectExceptionMessage('Gagal membaca data SAP untuk periode');

        $service->import($path, false);

        @unlink($path);
    }

    public function test_chunked_sap_matching_matches_single_range_results(): void
    {
        $this->createProject('017C', '02-SPT');

        $sapRowJanuary = [
            'grpo_no' => 'G1',
            'ito_no' => 'ITO-JAN',
            'ito_date' => Carbon::parse('2026-01-10'),
            'ito_created_date' => Carbon::parse('2026-01-10'),
            'iti_no' => null,
            'iti_date' => null,
            'item_code' => 'PART-JAN',
            'description' => 'Desc',
            'uom' => 'PCS',
            'qty' => 1.0,
            'po_no' => 'PO',
            'pr_no' => null,
            'mr_no' => null,
            'unit_no' => 'U1',
            'vendor' => 'V',
            'from_warehouse' => '01',
            'to_warehouse' => '02-SPT',
            'delivery_status' => 'Not Delivered',
            'delivery_date' => null,
            'remarks' => null,
        ];

        $sapRowMarch = array_merge($sapRowJanuary, [
            'ito_no' => 'ITO-MAR',
            'item_code' => 'PART-MAR',
            'ito_date' => Carbon::parse('2026-03-04'),
        ]);

        $allRows = collect([$sapRowJanuary, $sapRowMarch]);

        $fake = Mockery::mock(DeliveryPartQueryService::class);
        $fake->shouldReceive('fetchRowsWithoutCache')
            ->andReturnUsing(function (Carbon $from, Carbon $to) use ($allRows) {
                return $allRows->filter(function (array $row) use ($from, $to) {
                    $date = $row['ito_date'];
                    if (! $date instanceof Carbon) {
                        return false;
                    }

                    return $date->betweenIncluded($from->copy()->startOfDay(), $to->copy()->endOfDay());
                })->values();
            });
        $this->app->instance(DeliveryPartQueryService::class, $fake);

        $path = storage_path('app/testing-delivery-part-parity.xlsx');
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('017C');
        $sheet->setCellValue('B5', 'TANGGAL RECEIVED');
        $sheet->setCellValue('F5', 'NO ITO');
        $sheet->setCellValue('H5', 'Parts Number');
        $sheet->setCellValue('B7', '2026-01-05');
        $sheet->setCellValue('F7', 'ITO-JAN');
        $sheet->setCellValue('H7', 'PART-JAN');
        $sheet->setCellValue('I7', 'Deskripsi');
        $sheet->setCellValue('G7', 'U1');
        $sheet->setCellValue('B8', '2026-03-05');
        $sheet->setCellValue('F8', 'ITO-MAR');
        $sheet->setCellValue('H8', 'PART-MAR');
        $sheet->setCellValue('I8', 'Deskripsi');
        $sheet->setCellValue('G8', 'U1');
        (new Xlsx($spreadsheet))->save($path);

        $service = $this->app->make(DeliveryPartExcelImportService::class);
        $chunked = $service->import($path, false);

        $this->assertSame(2, $chunked['sheets']['017C']['will_create_sap']);
        $this->assertSame(0, $chunked['sheets']['017C']['will_create_manual']);

        @unlink($path);
    }

    public function test_import_restores_error_reporting_after_load(): void
    {
        $this->createProject('017C', '02-SPT');
        $this->bindEmptySap();

        $path = storage_path('app/testing-delivery-part-error-reporting.xlsx');
        $this->buildSampleExcel($path, [
            [
                'ito_no' => 'ITO-ER',
                'item_code' => 'PART-ER',
                'unit_no' => 'U1',
                'no_spb' => 'SPB-ER',
            ],
        ]);

        $expected = E_ALL & ~E_DEPRECATED;
        error_reporting($expected);

        $service = $this->app->make(DeliveryPartExcelImportService::class);
        $service->import($path, false);

        $this->assertSame($expected, error_reporting());

        @unlink($path);
    }

    public function test_empty_match_key_rows_always_create_manual_even_when_other_empty_key_rows_exist(): void
    {
        $project = Project::query()->create([
            'code' => 'PRATASABA',
            'owner' => 'Test',
            'location' => 'Test',
            'is_active' => true,
        ]);

        DeliveryPartEntry::query()->create([
            'project_id' => $project->id,
            'ito_no' => null,
            'item_code' => null,
            'unit_no' => null,
            'source' => DeliveryPartEntry::SOURCE_MANUAL,
            'no_spb' => 'SPB-EXISTING-EMPTY-KEY',
        ]);

        $this->bindEmptySap();

        $path = storage_path('app/testing-delivery-part-empty-key-pratasaba.xlsx');
        $this->buildSampleExcel($path, [
            [
                'ito_no' => '',
                'item_code' => '',
                'unit_no' => '',
                'description' => 'Baris PRATASABA A',
                'no_spb' => 'SPB-A',
            ],
            [
                'ito_no' => '',
                'item_code' => '',
                'unit_no' => '',
                'description' => 'Baris PRATASABA B',
                'no_spb' => 'SPB-B',
            ],
        ], [], 'PRATASABA');

        $this->artisan('delivery-part:import-excel', ['--file' => $path, '--write' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('Akan dibuat (manual): 2');

        $this->assertSame(3, DeliveryPartEntry::query()->where('project_id', $project->id)->count());
        $this->assertSame(2, DeliveryPartEntry::query()->where('project_id', $project->id)->whereIn('no_spb', ['SPB-A', 'SPB-B'])->count());

        @unlink($path);
    }

    public function test_duplicate_rows_with_identical_keys_are_reported_as_duplikat_dilewati_not_write_failures(): void
    {
        $project = $this->createProject('022C', '08-SPT');
        $this->bindEmptySap();

        $path = storage_path('app/testing-delivery-part-duplicates.xlsx');
        $this->buildSampleExcel($path, [
            [
                'ito_no' => 'ITO-DUP',
                'item_code' => 'PART-DUP',
                'unit_no' => 'UNIT-1',
                'no_spb' => 'SPB-FIRST',
            ],
            [
                'ito_no' => 'ITO-DUP',
                'item_code' => 'PART-DUP',
                'unit_no' => 'UNIT-1',
                'no_spb' => 'SPB-SECOND',
            ],
        ], [], '022C');

        $this->artisan('delivery-part:import-excel', ['--file' => $path, '--write' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('Duplikat dilewati: 1')
            ->expectsOutputToContain('Gagal tulis: 0');

        $this->assertDatabaseCount('delivery_part_entries', 1);
        $entry = DeliveryPartEntry::query()->where('project_id', $project->id)->first();
        $this->assertSame('SPB-FIRST', $entry->no_spb);

        @unlink($path);
    }

    public function test_unrecognized_ekspedisi_is_stored_trimmed_and_official_values_still_normalize(): void
    {
        $project = $this->createProject('017C', '02-SPT');
        $this->bindEmptySap();

        $path = storage_path('app/testing-delivery-part-ekspedisi-unknown.xlsx');
        $this->buildSampleExcel($path, [
            [
                'ito_no' => 'ITO-LV',
                'item_code' => 'PART-LV',
                'unit_no' => 'U1',
                'ekspedisi' => 'LV  ARKA',
            ],
            [
                'ito_no' => 'ITO-NAM',
                'item_code' => 'PART-NAM',
                'unit_no' => 'U2',
                'ekspedisi' => 'NAMARA JAYA',
            ],
        ]);

        $this->artisan('delivery-part:import-excel', ['--file' => $path, '--write' => true])
            ->assertExitCode(0);

        $lv = DeliveryPartEntry::query()->where('ito_no', 'ITO-LV')->first();
        $this->assertNotNull($lv);
        $this->assertSame('LV ARKA', $lv->ekspedisi);

        $nam = DeliveryPartEntry::query()->where('ito_no', 'ITO-NAM')->first();
        $this->assertSame('EKSPEDISI NAMARA', $nam->ekspedisi);

        @unlink($path);
    }

    public function test_duplicate_and_failure_lists_are_not_truncated_in_console_output(): void
    {
        $this->createProject('022C', '08-SPT');
        $this->bindEmptySap();

        $rows = [
            [
                'ito_no' => 'ITO-SEED',
                'item_code' => 'PART-SEED',
                'unit_no' => 'U-SEED',
                'no_spb' => 'SPB-SEED',
            ],
        ];

        for ($i = 1; $i <= 5; $i++) {
            $rows[] = [
                'ito_no' => 'ITO-SEED',
                'item_code' => 'PART-SEED',
                'unit_no' => 'U-SEED',
                'no_spb' => 'SPB-DUP-'.$i,
            ];
        }

        $path = storage_path('app/testing-delivery-part-many-dups.xlsx');
        $this->buildSampleExcel($path, $rows, [], '022C');

        Artisan::call('delivery-part:import-excel', ['--file' => $path]);
        $output = Artisan::output();

        $this->assertSame(5, substr_count($output, 'duplikat dalam file'));
        $this->assertStringNotContainsString('duplikat lainnya', $output);

        @unlink($path);
    }

    public function test_report_option_writes_all_duplicate_lines(): void
    {
        $this->createProject('022C', '08-SPT');
        $this->bindEmptySap();

        $rows = [
            [
                'ito_no' => 'ITO-RPT',
                'item_code' => 'PART-RPT',
                'unit_no' => 'U1',
                'no_spb' => 'SPB-1',
            ],
        ];
        for ($i = 0; $i < 3; $i++) {
            $rows[] = [
                'ito_no' => 'ITO-RPT',
                'item_code' => 'PART-RPT',
                'unit_no' => 'U1',
                'no_spb' => 'SPB-DUP',
            ];
        }

        $path = storage_path('app/testing-delivery-part-report.xlsx');
        $reportPath = storage_path('app/testing-delivery-part-report.csv');
        $this->buildSampleExcel($path, $rows, [], '022C');

        $this->artisan('delivery-part:import-excel', [
            '--file' => $path,
            '--report' => $reportPath,
        ])->assertExitCode(0);

        $this->assertFileExists($reportPath);
        $csv = file_get_contents($reportPath);
        $this->assertNotFalse($csv);
        $this->assertSame(3, substr_count($csv, 'duplicate'));

        @unlink($path);
        @unlink($reportPath);
    }

    public function test_second_import_run_is_idempotent_for_same_data(): void
    {
        $project = $this->createProject('017C', '02-SPT');
        $this->bindEmptySap();

        $path = storage_path('app/testing-delivery-part-idempotent.xlsx');
        $this->buildSampleExcel($path, [
            [
                'ito_no' => 'ITO-IDEM',
                'item_code' => 'PART-IDEM',
                'unit_no' => 'U1',
                'no_spb' => 'SPB-IDEM',
                'transporter' => 'Kurir',
            ],
        ]);

        $this->artisan('delivery-part:import-excel', ['--file' => $path, '--write' => true])
            ->assertExitCode(0);

        $this->assertDatabaseCount('delivery_part_entries', 1);

        Artisan::call('delivery-part:import-excel', ['--file' => $path, '--write' => true]);
        $output = Artisan::output();

        $this->assertDatabaseCount('delivery_part_entries', 1);
        $this->assertStringContainsString('Cocok dengan DDS: 1', $output);
        $this->assertStringContainsString('Konflik (sudah terisi): 1', $output);

        $entry = DeliveryPartEntry::query()->where('project_id', $project->id)->first();
        $this->assertSame('SPB-IDEM', $entry->no_spb);
        $this->assertSame('Kurir', $entry->transporter);

        @unlink($path);
    }
}
