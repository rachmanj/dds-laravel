<?php

namespace Tests\Feature;

use App\Services\SapService;
use Carbon\Carbon;
use Database\Seeders\AdditionalDocumentTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SapSyncDoFromGrpoCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdditionalDocumentTypeSeeder::class);
    }

    public function test_command_defaults_to_today_in_wita(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-04 10:00:00', 'Asia/Makassar'));

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('executeGrpoDoSqlQuery')
                ->once()
                ->with('2026-09-04')
                ->andReturn([]);
        });

        $this->artisan('sap:sync-do-from-grpo')
            ->assertSuccessful()
            ->expectsOutputToContain('SAP GRPO → DO sync: 2026-09-04');
    }

    public function test_command_yesterday_option(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-04 10:00:00', 'Asia/Makassar'));

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('executeGrpoDoSqlQuery')
                ->once()
                ->with('2026-09-03')
                ->andReturn([]);
        });

        $this->artisan('sap:sync-do-from-grpo', ['--yesterday' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('SAP GRPO → DO sync: 2026-09-03');
    }

    public function test_command_range_option_runs_for_each_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-04 10:00:00', 'Asia/Makassar'));

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('executeGrpoDoSqlQuery')
                ->once()
                ->with('2026-09-01')
                ->andReturn([]);
            $mock->shouldReceive('executeGrpoDoSqlQuery')
                ->once()
                ->with('2026-09-02')
                ->andReturn([]);
            $mock->shouldReceive('executeGrpoDoSqlQuery')
                ->once()
                ->with('2026-09-03')
                ->andReturn([]);
        });

        $this->artisan('sap:sync-do-from-grpo', [
            '--start' => '2026-09-01',
            '--end' => '2026-09-03',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('SAP GRPO → DO sync: 2026-09-01')
            ->expectsOutputToContain('SAP GRPO → DO sync: 2026-09-03');
    }

    public function test_command_rejects_partial_range(): void
    {
        $this->artisan('sap:sync-do-from-grpo', ['--start' => '2026-09-01'])
            ->assertFailed();
    }

    public function test_command_rejects_conflicting_modes(): void
    {
        $this->artisan('sap:sync-do-from-grpo', [
            '--today' => true,
            '--yesterday' => true,
        ])->assertFailed();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
