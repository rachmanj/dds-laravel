<?php

namespace Tests\Feature;

use App\Jobs\CreateSapApInvoiceJob;
use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SapApInvoicePayloadBuilder;
use App\Services\SapService;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SapApInvoicePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
    }

    protected function createSapReadyInvoice(User $user, array $overrides = []): Invoice
    {
        $typeId = InvoiceType::query()->firstOrFail()->id;
        $supplier = Supplier::query()->create([
            'sap_code' => 'V-SAP',
            'name' => 'SAP Vendor',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $date = now()->toDateString();

        return Invoice::query()->create(array_merge([
            'invoice_number' => 'INV-SAP-'.uniqid(),
            'faktur_no' => null,
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'po_no' => '12345',
            'currency' => 'IDR',
            'amount' => 1_000_000,
            'type_id' => $typeId,
            'created_by' => $user->id,
            'status' => 'sap',
            'cur_loc' => 'LOC1',
        ], $overrides));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function sampleGrpoResponse(): array
    {
        return [
            [
                'DocEntry' => 99,
                'DocNum' => 5001,
                'CardCode' => 'V-SAP',
                'DocumentLines' => [
                    [
                        'LineNum' => 0,
                        'ItemCode' => 'ITEM-A',
                        'Quantity' => 10,
                        'RemainingOpenQuantity' => 10,
                        'Price' => 50000,
                    ],
                    [
                        'LineNum' => 1,
                        'ItemCode' => 'ITEM-B',
                        'Quantity' => 5,
                        'RemainingOpenQuantity' => 5,
                        'Price' => 100000,
                    ],
                ],
            ],
        ];
    }

    public function test_preview_page_loads_grpo_lines_from_po(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user);

        $grpos = $this->sampleGrpoResponse();
        $this->mock(SapService::class, function ($mock) use ($grpos) {
            $mock->shouldReceive('getGrposByPoNumber')
                ->with('12345')
                ->once()
                ->andReturn($grpos);
        });

        $response = $this->actingAs($user)->get(route('invoices.sap-preview', $invoice));

        $response->assertOk();
        $response->assertSee('SAP AP Invoice Preview');
        $response->assertSee('GRPO-based');
        $response->assertSee('5001');
        $response->assertSee('99');
        $response->assertSee('ITEM-A');
        $response->assertSee('ITEM-B');
    }

    public function test_preview_standalone_when_no_po_no(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user, ['po_no' => null]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('getGrposByPoNumber');
        });

        $response = $this->actingAs($user)->get(route('invoices.sap-preview', $invoice));

        $response->assertOk();
        $response->assertSee('Standalone');
        $response->assertSee('No PO number');
        $response->assertDontSee('GRPO Lines (SAP Relationship Map)');
    }

    public function test_submit_dispatches_job_with_grpo_line_references(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user);

        $response = $this->actingAs($user)->post(route('invoices.submit-to-sap', $invoice), [
            'grpo_references' => [
                [
                    'grpo_no' => '5001',
                    'doc_entry' => 99,
                    'base_line' => 0,
                    'item_code' => 'ITEM-A',
                    'quantity' => 10,
                    'unit_price' => 50000,
                ],
            ],
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame('pending', $invoice->sap_status);
        $this->assertCount(1, $invoice->sap_grpo_references);
        $this->assertSame('ITEM-A', $invoice->sap_grpo_references[0]['item_code']);
        $this->assertSame(0, $invoice->sap_grpo_references[0]['base_line']);

        Queue::assertPushed(CreateSapApInvoiceJob::class);
    }

    public function test_submit_standalone_succeeds_without_grpo_refs(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user, ['po_no' => null]);

        $response = $this->actingAs($user)->post(route('invoices.submit-to-sap', $invoice), [
            'grpo_references' => [],
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('success');
        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, 'standalone'));

        $invoice->refresh();
        $this->assertSame('pending', $invoice->sap_status);
        $this->assertNull($invoice->sap_grpo_references);

        Queue::assertPushed(CreateSapApInvoiceJob::class);
    }

    public function test_submit_requires_grpo_when_po_no_is_set(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user, ['po_no' => '99999']);

        $response = $this->actingAs($user)->post(route('invoices.submit-to-sap', $invoice), [
            'grpo_references' => [],
        ]);

        $response->assertSessionHasErrors('grpo_references');
    }

    public function test_payload_builder_maps_posting_date_faktur_fields(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createSapReadyInvoice($user, [
            'invoice_date' => '2026-03-15',
            'receive_date' => '2026-03-20',
            'faktur_no' => '04002600101856924',
            'po_no' => null,
        ]);

        $builder = new SapApInvoicePayloadBuilder($invoice);
        $payload = $builder->build();
        $preview = $builder->getPreviewData()['ap_invoice'];

        $this->assertSame('2026-03-20', $payload['DocDate']);
        $this->assertSame('2026-03-15', $payload['U_MIS_FPDate']);
        $this->assertSame('04002600101856924', $payload['U_MIS_FPNum']);
        $this->assertSame('2026-03-20', $preview['posting_date']);
        $this->assertSame('04002600101856924', $preview['faktur_no']);
        $this->assertSame('2026-03-15', $preview['faktur_date']);
        $this->assertSame(['U_MIS_FPDate' => '2026-03-15', 'U_MIS_FPNum' => '04002600101856924'], $builder->buildFakturPatchFields());
    }

    public function test_payload_builder_omits_faktur_num_when_faktur_no_is_null(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createSapReadyInvoice($user, [
            'faktur_no' => null,
            'po_no' => null,
        ]);

        $builder = new SapApInvoicePayloadBuilder($invoice);
        $payload = $builder->build();

        $this->assertArrayNotHasKey('U_MIS_FPNum', $payload);
        $this->assertArrayHasKey('U_MIS_FPDate', $payload);
    }

    public function test_payload_builder_includes_base_document_fields_per_grpo_line(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createSapReadyInvoice($user);

        $builder = new SapApInvoicePayloadBuilder($invoice, [
            [
                'grpo_no' => '5001',
                'doc_entry' => 99,
                'base_line' => 0,
                'item_code' => 'ITEM-A',
                'quantity' => 10,
                'unit_price' => 50000,
            ],
            [
                'grpo_no' => '5001',
                'doc_entry' => 99,
                'base_line' => 1,
                'item_code' => 'ITEM-B',
                'quantity' => 5,
                'unit_price' => 100000,
            ],
        ]);

        $payload = $builder->build();

        $this->assertCount(2, $payload['DocumentLines']);
        $this->assertSame(20, $payload['DocumentLines'][0]['BaseType']);
        $this->assertSame(99, $payload['DocumentLines'][0]['BaseEntry']);
        $this->assertSame(0, $payload['DocumentLines'][0]['BaseLine']);
        $this->assertSame('ITEM-A', $payload['DocumentLines'][0]['ItemCode']);
        $this->assertSame(10.0, $payload['DocumentLines'][0]['Quantity']);
        $this->assertSame(1, $payload['DocumentLines'][1]['BaseLine']);
        $this->assertSame('ITEM-B', $payload['DocumentLines'][1]['ItemCode']);
        $this->assertSame(5.0, $payload['DocumentLines'][1]['Quantity']);
    }

    public function test_payload_builder_falls_back_to_standalone_line_without_grpo_refs(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createSapReadyInvoice($user, ['po_no' => null]);

        $builder = new SapApInvoicePayloadBuilder($invoice);
        $payload = $builder->build();

        $this->assertCount(1, $payload['DocumentLines']);
        $this->assertArrayNotHasKey('BaseType', $payload['DocumentLines'][0]);
        $this->assertSame((float) $invoice->amount, (float) $payload['DocumentLines'][0]['UnitPrice']);
    }

    public function test_user_without_send_to_sap_permission_cannot_access_preview(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('logistic');
        $invoice = $this->createSapReadyInvoice($user);

        $response = $this->actingAs($user)->get(route('invoices.sap-preview', $invoice));

        $response->assertForbidden();
    }

    public function test_preview_resolves_multiple_po_numbers(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user, ['po_no' => '1001, 1002;1003']);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getGrposByPoNumber')
                ->times(3)
                ->andReturnUsing(function (string $poNo) {
                    return [
                        [
                            'DocEntry' => (int) $poNo,
                            'DocNum' => (int) $poNo + 4000,
                            'CardCode' => 'V-SAP',
                            'DocumentLines' => [
                                [
                                    'LineNum' => 0,
                                    'ItemCode' => 'ITEM-'.$poNo,
                                    'Quantity' => 1,
                                    'RemainingOpenQuantity' => 1,
                                    'Price' => 100,
                                ],
                            ],
                        ],
                    ];
                });
        });

        $response = $this->actingAs($user)->get(route('invoices.sap-preview', $invoice));

        $response->assertOk();
        $response->assertSee('ITEM-1001');
        $response->assertSee('ITEM-1002');
        $response->assertSee('ITEM-1003');
    }

    public function test_sap_status_endpoint_returns_current_state(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user, [
            'sap_status' => 'pending',
            'sap_doc_num' => null,
        ]);

        $response = $this->actingAs($user)->getJson(route('invoices.sap-status', $invoice));

        $response->assertOk();
        $response->assertJson([
            'sap_status' => 'pending',
            'is_terminal' => false,
        ]);
        $response->assertJsonStructure(['sap_status_badge', 'display_sap_document']);
    }

    public function test_submit_returns_json_for_ajax_requests(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user, ['po_no' => null]);

        $response = $this->actingAs($user)->postJson(route('invoices.submit-to-sap', $invoice), [
            'grpo_references' => [],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'sap_status' => 'pending',
        ]);
        $response->assertJsonStructure(['status_url', 'invoice_url']);
    }

    public function test_legacy_sap_doc_blocks_preview_submission_and_sync(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user, [
            'sap_doc' => '9001234',
            'sap_status' => null,
        ]);

        $this->assertTrue($invoice->has_legacy_sap_doc);
        $this->assertNotEmpty($invoice->canSyncToSap());

        $previewResponse = $this->actingAs($user)->get(route('invoices.sap-preview', $invoice));
        $previewResponse->assertRedirect(route('invoices.show', $invoice));
        $previewResponse->assertSessionHasErrors('sap_sync');

        $submitResponse = $this->actingAs($user)->post(route('invoices.submit-to-sap', $invoice), [
            'grpo_references' => [],
        ]);
        $submitResponse->assertSessionHasErrors('sap_sync');

        $syncResponse = $this->actingAs($user)->post(route('invoices.sap-sync', $invoice));
        $syncResponse->assertSessionHasErrors('sap_sync');

        $statusResponse = $this->actingAs($user)->getJson(route('invoices.sap-status', $invoice));
        $statusResponse->assertOk();
        $statusResponse->assertJson([
            'show_send_button' => false,
            'show_retry_button' => false,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_legacy_sap_doc_renders_legacy_badge(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createSapReadyInvoice($user, [
            'sap_doc' => '9001234',
            'sap_status' => null,
        ]);

        $this->assertStringContainsString('SAP Doc: 9001234 (Legacy)', $invoice->sap_status_badge);
        $this->assertStringNotContainsString('Not Sent to SAP', $invoice->sap_status_badge);
    }

    public function test_open_status_invoice_can_submit_to_sap(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user, [
            'status' => 'open',
            'po_no' => null,
        ]);

        $this->assertEmpty($invoice->canSyncToSap());

        $response = $this->actingAs($user)->post(route('invoices.submit-to-sap', $invoice), [
            'grpo_references' => [],
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame('open', $invoice->status);
        $this->assertSame('pending', $invoice->sap_status);

        Queue::assertPushed(CreateSapApInvoiceJob::class);
    }

    public function test_cancelled_invoice_cannot_submit_to_sap(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user, [
            'status' => 'cancel',
            'po_no' => null,
        ]);

        $this->assertNotEmpty($invoice->canSyncToSap());

        $response = $this->actingAs($user)->post(route('invoices.submit-to-sap', $invoice), [
            'grpo_references' => [],
        ]);

        $response->assertSessionHasErrors('sap_sync');

        $statusResponse = $this->actingAs($user)->getJson(route('invoices.sap-status', $invoice));
        $statusResponse->assertOk();
        $statusResponse->assertJson([
            'show_send_button' => false,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_successful_post_sets_status_to_sap(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createSapReadyInvoice($user, [
            'status' => 'open',
            'po_no' => null,
            'sap_status' => 'pending',
            'invoice_date' => '2026-03-15',
            'receive_date' => '2026-03-20',
            'faktur_no' => '04002600101856924',
        ]);
        $invoice->load('supplier');

        $this->mock(SapService::class, function ($mock) use ($invoice) {
            $mock->shouldReceive('getBusinessPartner')
                ->once()
                ->with($invoice->supplier->sap_code)
                ->andReturn([
                    'CardCode' => $invoice->supplier->sap_code,
                    'CardName' => 'SAP Vendor',
                    'CardType' => 'S',
                ]);

            $mock->shouldReceive('createApInvoice')
                ->once()
                ->with(\Mockery::on(function (array $payload): bool {
                    return $payload['DocDate'] === '2026-03-20'
                        && $payload['U_MIS_FPDate'] === '2026-03-15'
                        && $payload['U_MIS_FPNum'] === '04002600101856924';
                }))
                ->andReturn([
                    'DocNum' => 7001,
                    'DocEntry' => 88,
                ]);

            $mock->shouldReceive('updateApInvoice')
                ->once()
                ->with(88, \Mockery::on(function (array $fields): bool {
                    return $fields['U_MIS_FPDate'] === '2026-03-15'
                        && $fields['U_MIS_FPNum'] === '04002600101856924';
                }))
                ->andReturn([]);
        });

        $job = new CreateSapApInvoiceJob($invoice, []);
        $job->handle(app(SapService::class));

        $invoice->refresh();
        $this->assertSame('sap', $invoice->status);
        $this->assertSame('posted', $invoice->sap_status);
        $this->assertSame('7001', $invoice->sap_doc_num);
    }
}
