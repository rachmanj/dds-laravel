<?php

namespace Tests\Feature;

use App\Jobs\SyncSapDoFromGrpoJob;
use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\User;
use App\Services\SapService;
use Database\Seeders\AdditionalDocumentTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SyncSapDoFromGrpoJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdditionalDocumentTypeSeeder::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sampleGrpoRows(): array
    {
        return [
            [
                'DocEntry' => 1001,
                'DocNum' => 2605001,
                'CardCode' => 'VCASJIDR01',
                'CardName' => 'Vendor A',
                'NumAtCard' => 'DO-TEST-001',
                'TaxDate' => '2026-09-03',
                'DocDate' => '2026-09-03',
                'CreateDate' => '2026-09-03',
                'PoNo' => '250206314',
                'Project' => '022C',
            ],
            [
                'DocEntry' => 1002,
                'DocNum' => 2605002,
                'CardCode' => 'VCASJIDR02',
                'CardName' => 'Vendor B',
                'NumAtCard' => '26/019753',
                'TaxDate' => '2026-09-03',
                'DocDate' => '2026-09-02',
                'CreateDate' => '2026-09-03',
                'PoNo' => '250206240',
                'Project' => '017C',
            ],
        ];
    }

    public function test_job_creates_do_records_with_correct_mapping(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $doType = AdditionalDocumentType::query()->where('type_name', 'Delivery Order (DO)')->firstOrFail();
        $targetDate = '2026-09-03';

        $this->mock(SapService::class, function ($mock) use ($targetDate) {
            $mock->shouldReceive('executeGrpoDoSqlQuery')
                ->once()
                ->with($targetDate)
                ->andReturn($this->sampleGrpoRows());
        });

        $job = new SyncSapDoFromGrpoJob($targetDate, [
            'trigger' => 'cli',
            'triggered_by_user_id' => $user->id,
        ]);
        $job->handle(app(SapService::class));

        $first = AdditionalDocument::query()->where('grpo_no', '2605001')->firstOrFail();
        $this->assertSame($doType->id, $first->type_id);
        $this->assertSame('DO-TEST-001', $first->document_number);
        $this->assertSame('2026-09-03', $first->document_date->format('Y-m-d'));
        $this->assertSame('2026-09-03', $first->receive_date->format('Y-m-d'));
        $this->assertSame('250206314', $first->po_no);
        $this->assertSame('VCASJIDR01', $first->vendor_code);
        $this->assertSame('022C', $first->project);
        $this->assertSame('000HACC', $first->cur_loc);
        $this->assertSame($user->id, $first->created_by);
        $this->assertSame('open', $first->status);
        $this->assertSame('Auto-sync dari GRPO 2605001', $first->remarks);

        $second = AdditionalDocument::query()->where('grpo_no', '2605002')->firstOrFail();
        $this->assertSame('26/019753', $second->document_number);
        $this->assertSame('2026-09-02', $second->receive_date->format('Y-m-d'));
        $this->assertSame('Auto-sync dari GRPO 2605002', $second->remarks);

        $this->assertSame(2, AdditionalDocument::query()->where('type_id', $doType->id)->count());

        $log = DB::table('sap_logs')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('query_sync', $log->action);
        $this->assertSame('success', $log->status);

        $request = json_decode($log->request_payload, true);
        $this->assertSame($targetDate, $request['target_date']);
        $this->assertSame('grpo_do', $request['sync_type']);
        $this->assertSame('cli', $request['trigger']);
        $this->assertSame($user->id, $request['triggered_by_user_id']);

        $response = json_decode($log->response_payload, true);
        $this->assertSame(2, $response['fetched']);
        $this->assertSame(2, $response['success']);
        $this->assertSame(0, $response['skipped']);
    }

    public function test_job_skips_duplicate_grpo_no(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $doType = AdditionalDocumentType::query()->where('type_name', 'Delivery Order (DO)')->firstOrFail();
        $targetDate = '2026-09-03';

        AdditionalDocument::query()->create([
            'type_id' => $doType->id,
            'document_number' => 'EXISTING-DO',
            'document_date' => '2026-09-01',
            'receive_date' => '2026-09-01',
            'grpo_no' => '2605001',
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => '000HACC',
        ]);

        $this->mock(SapService::class, function ($mock) use ($targetDate) {
            $mock->shouldReceive('executeGrpoDoSqlQuery')
                ->once()
                ->with($targetDate)
                ->andReturn($this->sampleGrpoRows());
        });

        $job = new SyncSapDoFromGrpoJob($targetDate, [
            'trigger' => 'cli',
            'triggered_by_user_id' => $user->id,
        ]);
        $job->handle(app(SapService::class));

        $this->assertSame(2, AdditionalDocument::query()->where('type_id', $doType->id)->count());
        $this->assertDatabaseHas('additional_documents', [
            'grpo_no' => '2605002',
            'document_number' => '26/019753',
        ]);

        $log = DB::table('sap_logs')->latest('id')->first();
        $response = json_decode($log->response_payload, true);
        $this->assertSame(2, $response['fetched']);
        $this->assertSame(1, $response['success']);
        $this->assertSame(1, $response['skipped']);
    }

    public function test_job_writes_failed_sap_log_on_exception(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $targetDate = '2026-09-03';

        $this->mock(SapService::class, function ($mock) use ($targetDate) {
            $mock->shouldReceive('executeGrpoDoSqlQuery')
                ->once()
                ->with($targetDate)
                ->andThrow(new \RuntimeException('SAP connection failed'));
        });

        $job = new SyncSapDoFromGrpoJob($targetDate, [
            'trigger' => 'cli',
            'triggered_by_user_id' => $user->id,
        ]);

        try {
            $job->handle(app(SapService::class));
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('SAP connection failed', $e->getMessage());
        }

        $log = DB::table('sap_logs')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('failed', $log->status);
        $this->assertSame('SAP connection failed', $log->error_message);
    }
}
