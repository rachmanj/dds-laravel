<?php

namespace Tests\Unit;

use App\Models\DeliveryPartItoCancel;
use App\Models\User;
use App\Services\SapService;
use App\Support\ItoCancelGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ItoCancelGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_guard_rejects_short_reason(): void
    {
        $sap = Mockery::mock(SapService::class);
        $guard = new ItoCancelGuard($sap);

        $result = $guard->validate(1, '100', 'short');

        $this->assertFalse($result['allowed']);
    }

    public function test_guard_rejects_active_duplicate(): void
    {
        $user = User::factory()->create();
        DeliveryPartItoCancel::query()->create([
            'doc_entry' => 55,
            'ito_no' => 'ITO-55',
            'reason' => 'Sudah ada permintaan aktif sebelumnya',
            'status' => DeliveryPartItoCancel::STATUS_REQUESTED,
            'requested_by' => $user->id,
            'requested_at' => now(),
        ]);

        $sap = Mockery::mock(SapService::class);
        $sap->shouldReceive('stockTransferExists')->with(55)->andReturn(true);
        $sap->shouldReceive('hasItiForIto')->andReturn(false);
        $sap->shouldReceive('isStockTransferCancelled')->with(55)->andReturn(false);

        $guard = new ItoCancelGuard($sap);
        $result = $guard->validate(55, 'ITO-55', 'Alasan yang memenuhi panjang minimum');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('permintaan pembatalan aktif', $result['message']);
    }
}
