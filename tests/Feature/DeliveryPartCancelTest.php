<?php

namespace Tests\Feature;

use App\Models\DeliveryPartItoCancel;
use App\Models\User;
use App\Services\SapService;
use Database\Seeders\DeliveryPartPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DeliveryPartCancelTest extends TestCase
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

    private function userWithCancelPermission(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(['view-delivery-part', 'cancel-ito']);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function bindSapMock(array $overrides = []): SapService
    {
        $mock = Mockery::mock(SapService::class);

        $mock->shouldReceive('stockTransferExists')
            ->andReturn($overrides['stockTransferExists'] ?? true);

        $mock->shouldReceive('hasItiForIto')
            ->andReturn($overrides['hasItiForIto'] ?? false);

        if (array_key_exists('isStockTransferCancelled', $overrides)) {
            $mock->shouldReceive('isStockTransferCancelled')
                ->andReturn($overrides['isStockTransferCancelled']);
        } else {
            $mock->shouldReceive('isStockTransferCancelled')
                ->andReturn(false, true);
        }

        if (array_key_exists('cancelStockTransfer', $overrides)) {
            $mock->shouldReceive('cancelStockTransfer')
                ->once()
                ->andReturn($overrides['cancelStockTransfer']);
        } else {
            $mock->shouldReceive('cancelStockTransfer')
                ->once()
                ->andReturn([
                    'ok' => true,
                    'http_status' => 204,
                    'message' => 'Pembatalan diterima SAP (HTTP 204).',
                ]);
        }

        $this->app->instance(SapService::class, $mock);

        return $mock;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(?int $projectId = null): array
    {
        return [
            'doc_entry' => 41261,
            'ito_no' => '261006045',
            'project_id' => $projectId,
            'item_code' => '08-OIL',
            'unit_no' => 'U-1',
            'reason' => 'Alasan pembatalan uji yang cukup panjang',
        ];
    }

    public function test_cancel_success_verified_records_cancelled_with_audit_fields(): void
    {
        $user = $this->userWithCancelPermission();
        $this->bindSapMock();

        $response = $this->actingAs($user)->postJson(
            route('logistics.delivery-part.cancel.store'),
            $this->validPayload()
        );

        $response->assertOk();
        $cancel = DeliveryPartItoCancel::query()->first();
        $this->assertNotNull($cancel);
        $this->assertSame(DeliveryPartItoCancel::STATUS_CANCELLED, $cancel->status);
        $this->assertSame($user->id, $cancel->requested_by);
        $this->assertNotNull($cancel->requested_at);
        $this->assertNotNull($cancel->executed_at);
        $this->assertNotNull($cancel->verified_at);
        $this->assertSame('Alasan pembatalan uji yang cukup panjang', $cancel->reason);
    }

    public function test_http_204_but_not_verified_stays_requested(): void
    {
        $user = $this->userWithCancelPermission();
        $this->bindSapMock([
            'isStockTransferCancelled' => false,
        ]);

        $response = $this->actingAs($user)->postJson(
            route('logistics.delivery-part.cancel.store'),
            $this->validPayload()
        );

        $response->assertOk();
        $cancel = DeliveryPartItoCancel::query()->firstOrFail();
        $this->assertSame(DeliveryPartItoCancel::STATUS_REQUESTED, $cancel->status);
        $this->assertNull($cancel->verified_at);
        $this->assertStringContainsString('belum terbaca', (string) $cancel->sap_message);
    }

    public function test_sap_failure_sets_failed_status_and_message(): void
    {
        $user = $this->userWithCancelPermission();
        $this->bindSapMock([
            'cancelStockTransfer' => [
                'ok' => false,
                'http_status' => 400,
                'message' => 'SAP menolak pembatalan dokumen',
            ],
        ]);

        $response = $this->actingAs($user)->postJson(
            route('logistics.delivery-part.cancel.store'),
            $this->validPayload()
        );

        $response->assertStatus(422);
        $cancel = DeliveryPartItoCancel::query()->firstOrFail();
        $this->assertSame(DeliveryPartItoCancel::STATUS_FAILED, $cancel->status);
        $this->assertSame('SAP menolak pembatalan dokumen', $cancel->sap_message);
    }

    public function test_rejects_when_ito_already_has_iti(): void
    {
        $user = $this->userWithCancelPermission();
        $mock = Mockery::mock(SapService::class);
        $mock->shouldReceive('stockTransferExists')->andReturn(true);
        $mock->shouldReceive('hasItiForIto')->andReturn(true);
        $this->app->instance(SapService::class, $mock);

        $response = $this->actingAs($user)->postJson(
            route('logistics.delivery-part.cancel.store'),
            $this->validPayload()
        );

        $response->assertStatus(422);
        $response->assertJson(['message' => 'ITO ini sudah memiliki ITI, tidak bisa dibatalkan.']);
        $this->assertSame(0, DeliveryPartItoCancel::query()->count());
    }

    public function test_rejects_reason_shorter_than_ten_characters(): void
    {
        $user = $this->userWithCancelPermission();

        $response = $this->actingAs($user)->postJson(
            route('logistics.delivery-part.cancel.store'),
            array_merge($this->validPayload(), ['reason' => 'pendek'])
        );

        $response->assertStatus(422);
        $this->assertSame(0, DeliveryPartItoCancel::query()->count());
    }

    public function test_rejects_duplicate_active_request(): void
    {
        $user = $this->userWithCancelPermission();
        DeliveryPartItoCancel::query()->create([
            'doc_entry' => 41261,
            'ito_no' => '261006045',
            'reason' => 'Permintaan sebelumnya yang masih aktif',
            'status' => DeliveryPartItoCancel::STATUS_REQUESTED,
            'requested_by' => $user->id,
            'requested_at' => now(),
        ]);

        $mock = Mockery::mock(SapService::class);
        $mock->shouldReceive('stockTransferExists')->andReturn(true);
        $mock->shouldReceive('hasItiForIto')->andReturn(false);
        $mock->shouldReceive('isStockTransferCancelled')->andReturn(false);
        $this->app->instance(SapService::class, $mock);

        $response = $this->actingAs($user)->postJson(
            route('logistics.delivery-part.cancel.store'),
            $this->validPayload()
        );

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Sudah ada permintaan pembatalan aktif untuk dokumen ITO ini.']);
        $this->assertSame(1, DeliveryPartItoCancel::query()->count());
    }

    public function test_user_without_cancel_permission_gets_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo('view-delivery-part');

        $response = $this->actingAs($user)->postJson(
            route('logistics.delivery-part.cancel.store'),
            $this->validPayload()
        );

        $response->assertForbidden();
    }

    public function test_verify_cancels_command_marks_requested_as_cancelled_when_sap_yes(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $cancel = DeliveryPartItoCancel::query()->create([
            'doc_entry' => 999,
            'ito_no' => 'ITO-999',
            'reason' => 'Menunggu verifikasi manual di SAP',
            'status' => DeliveryPartItoCancel::STATUS_REQUESTED,
            'requested_by' => $user->id,
            'requested_at' => now()->subHour(),
        ]);

        $mock = Mockery::mock(SapService::class);
        $mock->shouldReceive('isStockTransferCancelled')
            ->with(999)
            ->andReturn(true);
        $this->app->instance(SapService::class, $mock);

        $this->artisan('delivery-part:verify-cancels')
            ->assertExitCode(0);

        $cancel->refresh();
        $this->assertSame(DeliveryPartItoCancel::STATUS_CANCELLED, $cancel->status);
        $this->assertNotNull($cancel->verified_at);
    }
}
