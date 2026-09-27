<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SapService;
use App\Support\SapSubmittedByStamp;
use Carbon\Carbon;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SapBackfillSubmittedByCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
    }

    protected function createPostedInvoiceWithSubmitter(User $submitter, array $overrides = []): Invoice
    {
        $creator = User::factory()->create(['is_active' => true]);
        $typeId = InvoiceType::query()->firstOrFail()->id;
        $supplier = Supplier::query()->create([
            'sap_code' => 'V-SAP',
            'name' => 'SAP Vendor',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $creator->id,
        ]);

        $date = now()->toDateString();
        $submittedAt = Carbon::parse('2026-09-25 11:41:00', config('app.timezone'));

        $data = array_merge([
            'invoice_number' => 'INV-BF-'.uniqid(),
            'faktur_no' => null,
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'po_no' => null,
            'currency' => 'IDR',
            'amount' => 1_000_000,
            'type_id' => $typeId,
            'created_by' => $creator->id,
            'status' => 'sap',
            'cur_loc' => 'LOC1',
            'sap_status' => 'posted',
            'sap_doc_num' => '9001',
            'sap_doc_entry' => '501',
            'sap_doc' => '9001',
            'sap_submitted_by_user_id' => $submitter->id,
            'sap_submitted_at' => $submittedAt,
        ], $overrides);

        if (! array_key_exists('sap_doc', $overrides) && isset($data['sap_doc_num'])) {
            $data['sap_doc'] = (string) $data['sap_doc_num'];
        }

        return Invoice::query()->create($data);
    }

    public function test_command_without_mode_refuses_and_does_not_call_sap(): void
    {
        $submitter = User::factory()->create([
            'is_active' => true,
            'username' => 'elma',
        ]);
        $this->createPostedInvoiceWithSubmitter($submitter);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('getPurchaseInvoiceSubmittedUdf');
            $mock->shouldNotReceive('updateApInvoice');
        });

        $this->artisan('sap:backfill-submitted-by')
            ->assertFailed()
            ->expectsOutputToContain('--dry-run');
    }

    public function test_dry_run_does_not_patch_sap(): void
    {
        $submitter = User::factory()->create([
            'is_active' => true,
            'username' => 'elma',
        ]);
        $invoice = $this->createPostedInvoiceWithSubmitter($submitter, [
            'sap_doc_entry' => '1001',
            'sap_doc_num' => '7001',
            'invoice_number' => 'DDS-DRY-1',
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceSubmittedUdf')
                ->once()
                ->with('1001')
                ->andReturn([
                    'DocEntry' => 1001,
                    'DocNum' => 7001,
                    'U_MIS_Submitted' => null,
                ]);
            $mock->shouldNotReceive('updateApInvoice');
        });

        $this->assertSame('DDS-DRY-1', $invoice->invoice_number);
        $invoice->refresh();
        $expectedStamp = SapSubmittedByStamp::make('elma', $invoice->sap_submitted_at);
        $this->assertNotNull($expectedStamp);

        $this->assertSame(1, Invoice::query()->where('sap_status', 'posted')->count());

        $this->artisan('sap:backfill-submitted-by', [
            '--dry-run' => true,
            '--sleep' => 0,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('user=elma would_write='.$expectedStamp)
            ->expectsOutputToContain('Summary: candidates=1, would_write=1, already_filled=0');
    }

    public function test_skips_when_sap_udf_already_filled(): void
    {
        $submitter = User::factory()->create([
            'is_active' => true,
            'username' => 'elma',
        ]);
        $this->createPostedInvoiceWithSubmitter($submitter, [
            'sap_doc_entry' => '1002',
            'sap_doc_num' => '7002',
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceSubmittedUdf')
                ->once()
                ->with('1002')
                ->andReturn([
                    'DocEntry' => 1002,
                    'DocNum' => 7002,
                    'U_MIS_Submitted' => 'existing value',
                ]);
            $mock->shouldNotReceive('updateApInvoice');
        });

        $this->artisan('sap:backfill-submitted-by', [
            '--dry-run' => true,
            '--sleep' => 0,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('[already filled]')
            ->expectsOutputToContain('candidates=1, would_write=0, already_filled=1');
    }

    public function test_write_mode_sets_udf_using_helper_format(): void
    {
        $submitter = User::factory()->create([
            'is_active' => true,
            'username' => 'elma',
        ]);
        $invoice = $this->createPostedInvoiceWithSubmitter($submitter, [
            'sap_doc_entry' => '2001',
            'sap_doc_num' => '8001',
        ]);
        $invoice->refresh();
        $expected = SapSubmittedByStamp::make('elma', $invoice->sap_submitted_at);
        $this->assertNotNull($expected);

        $this->mock(SapService::class, function ($mock) use ($expected) {
            $mock->shouldReceive('getPurchaseInvoiceSubmittedUdf')
                ->once()
                ->with('2001')
                ->andReturn([
                    'DocEntry' => 2001,
                    'DocNum' => 8001,
                    'U_MIS_Submitted' => '',
                ]);
            $mock->shouldReceive('updateApInvoice')
                ->once()
                ->with('2001', ['U_MIS_Submitted' => $expected])
                ->andReturn([]);
        });

        $this->artisan('sap:backfill-submitted-by', [
            '--write' => true,
            '--sleep' => 0,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain("U_MIS_Submitted set to {$expected}")
            ->expectsOutputToContain('written=1');
    }

    public function test_one_write_failure_does_not_stop_following_document(): void
    {
        $submitter = User::factory()->create([
            'is_active' => true,
            'username' => 'elma',
        ]);

        $first = $this->createPostedInvoiceWithSubmitter($submitter, [
            'sap_doc_entry' => '3001',
            'sap_doc_num' => '9001',
            'invoice_number' => 'DDS-FAIL-FIRST',
        ]);
        $this->createPostedInvoiceWithSubmitter($submitter, [
            'sap_doc_entry' => '3002',
            'sap_doc_num' => '9002',
            'invoice_number' => 'DDS-OK-SECOND',
        ]);

        $first->refresh();
        $expected = SapSubmittedByStamp::make('elma', $first->sap_submitted_at);
        $this->assertNotNull($expected);

        $patchException = new RequestException(
            'PATCH failed',
            new Request('PATCH', 'PurchaseInvoices(3001)'),
            new Response(500, [], 'Server error')
        );

        $this->mock(SapService::class, function ($mock) use ($expected, $patchException) {
            $mock->shouldReceive('getPurchaseInvoiceSubmittedUdf')
                ->once()
                ->with('3001')
                ->andReturn([
                    'DocEntry' => 3001,
                    'DocNum' => 9001,
                    'U_MIS_Submitted' => null,
                ]);
            $mock->shouldReceive('updateApInvoice')
                ->once()
                ->with('3001', ['U_MIS_Submitted' => $expected])
                ->andThrow($patchException);
            $mock->shouldReceive('getPurchaseInvoiceSubmittedUdf')
                ->once()
                ->with('3002')
                ->andReturn([
                    'DocEntry' => 3002,
                    'DocNum' => 9002,
                    'U_MIS_Submitted' => null,
                ]);
            $mock->shouldReceive('updateApInvoice')
                ->once()
                ->with('3002', ['U_MIS_Submitted' => $expected])
                ->andReturn([]);
        });

        $this->artisan('sap:backfill-submitted-by', [
            '--write' => true,
            '--sleep' => 0,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('PATCH failed DocEntry 3001')
            ->expectsOutputToContain('OK DocEntry 3002')
            ->expectsOutputToContain('Summary: candidates=2, written=1, write_failed=1');
    }
}
