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
     * @param  array<int, array<string, mixed>>  $dataRows
     */
    private function buildSampleExcel(string $path, array $dataRows, bool $includeFullHeaders = true): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('017C');

        $sheet->setCellValue('A2', 'Delivery Part 017C - M');
        $sheet->setCellValue('A5', 'NO ITO');
        $sheet->mergeCells('B5:B6');
        $sheet->setCellValue('B5', 'Parts Number');
        $sheet->setCellValue('C5', 'No Unit');
        $sheet->setCellValue('D5', 'No. SPB');
        $sheet->setCellValue('E5', 'Remarks Barang');
        $sheet->setCellValue('F5', 'Tgl Delivery');
        $sheet->setCellValue('G5', 'Transporter');
        $sheet->setCellValue('H5', 'Unit & No Kendaraan');
        $sheet->mergeCells('I5:I6');
        $sheet->setCellValue('I5', 'Ekspedisi Pengirim');

        if (! $includeFullHeaders) {
            $sheet->setCellValue('A5', 'NO ITO');
            $sheet->setCellValue('B5', 'Parts Number');
        }

        $rowNum = 7;
        foreach ($dataRows as $row) {
            $sheet->setCellValue('A'.$rowNum, $row['ito_no'] ?? '');
            $sheet->setCellValue('B'.$rowNum, $row['item_code'] ?? '');
            $sheet->setCellValue('C'.$rowNum, $row['unit_no'] ?? '');
            $sheet->setCellValue('D'.$rowNum, $row['no_spb'] ?? '');
            $sheet->setCellValue('E'.$rowNum, $row['remarks_barang'] ?? '');
            $sheet->setCellValue('F'.$rowNum, $row['tgl_delivery'] ?? '');
            $sheet->setCellValue('G'.$rowNum, $row['transporter'] ?? '');
            $sheet->setCellValue('H'.$rowNum, $row['unit_kendaraan'] ?? '');
            $sheet->setCellValue('I'.$rowNum, $row['ekspedisi'] ?? '');
            $rowNum++;
        }

        $badSheet = $spreadsheet->createSheet();
        $badSheet->setTitle('022C');
        $badSheet->setCellValue('A5', 'NO ITO');
        $badSheet->setCellValue('B5', 'Parts Number');

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }

    private function bindEmptySap(): void
    {
        $fake = Mockery::mock(DeliveryPartQueryService::class);
        $fake->shouldReceive('rows')->andReturn(collect());
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

    public function test_sheet_without_required_headers_is_skipped(): void
    {
        $this->createProject('017C', '02-SPT');
        $this->createProject('022C', '08-SPT');
        $this->bindEmptySap();

        $path = storage_path('app/testing-delivery-part-skip.xlsx');
        $this->buildSampleExcel($path, [], false);

        $this->artisan('delivery-part:import-excel', ['--file' => $path])
            ->assertExitCode(0)
            ->expectsOutputToContain('Dilewati');

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
        $fake->shouldReceive('rows')->andReturn(Collection::make([$sapRow]));
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
}
