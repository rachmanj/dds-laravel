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

    public function test_preview_page_loads_for_finance_user(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getGrpoByDocNum')
                ->with('12345')
                ->once()
                ->andReturn([
                    'DocEntry' => 99,
                    'DocNum' => 12345,
                    'CardCode' => 'V-SAP',
                    'DocumentLines' => [
                        ['LineNum' => 0, 'LineTotal' => 500000],
                    ],
                ]);
        });

        $response = $this->actingAs($user)->get(route('invoices.sap-preview', $invoice));

        $response->assertOk();
        $response->assertSee('SAP AP Invoice Preview');
        $response->assertSee('12345');
        $response->assertSee('99');
    }

    public function test_submit_dispatches_job_with_grpo_references(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user);

        $response = $this->actingAs($user)->post(route('invoices.submit-to-sap', $invoice), [
            'grpo_references' => [
                [
                    'grpo_no' => '12345',
                    'doc_entry' => 99,
                    'amount' => 1000000,
                    'line' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame('pending', $invoice->sap_status);
        $this->assertCount(1, $invoice->sap_grpo_references);

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

    public function test_payload_builder_includes_base_document_fields_when_grpo_refs_provided(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createSapReadyInvoice($user);

        $builder = new SapApInvoicePayloadBuilder($invoice, [
            [
                'grpo_no' => '12345',
                'doc_entry' => 99,
                'amount' => 500000,
                'line' => 0,
            ],
            [
                'grpo_no' => '12346',
                'doc_entry' => 100,
                'amount' => 500000,
                'line' => 1,
            ],
        ]);

        $payload = $builder->build();

        $this->assertCount(2, $payload['DocumentLines']);
        $this->assertSame(20, $payload['DocumentLines'][0]['BaseType']);
        $this->assertSame(99, $payload['DocumentLines'][0]['BaseEntry']);
        $this->assertSame(0, $payload['DocumentLines'][0]['BaseLine']);
        $this->assertSame(100, $payload['DocumentLines'][1]['BaseEntry']);
        $this->assertSame(1, $payload['DocumentLines'][1]['BaseLine']);
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

    public function test_parse_multiple_grpo_numbers_from_po_no(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user, ['po_no' => '1001, 1002;1003']);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getGrpoByDocNum')->andReturn([
                'DocEntry' => 1,
                'DocNum' => 1,
                'DocumentLines' => [],
            ]);
        });

        $response = $this->actingAs($user)->get(route('invoices.sap-preview', $invoice));

        $response->assertOk();
        $response->assertSee('1001');
        $response->assertSee('1002');
        $response->assertSee('1003');
    }
}
