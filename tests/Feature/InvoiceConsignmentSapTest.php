<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceLineDetail;
use App\Models\InvoiceType;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SapApInvoicePayloadBuilder;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceConsignmentSapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
    }

    /**
     * @return array{0: User, 1: InvoiceType, 2: InvoiceType, 3: Supplier}
     */
    private function seedActors(): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');

        Project::query()->create([
            'code' => '001H',
            'owner' => 'HO',
            'location' => 'Jakarta',
            'is_active' => true,
        ]);

        $itemType = InvoiceType::query()->where('type_name', 'Item')->firstOrFail();
        $consignmentType = InvoiceType::query()->where('type_name', 'Consignment')->firstOrFail();
        $this->assertTrue($consignmentType->is_consignment);

        $supplier = Supplier::query()->create([
            'sap_code' => 'V-CON',
            'name' => 'Consignment Vendor',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        return [$user, $itemType, $consignmentType, $supplier];
    }

    /**
     * @return array<string, mixed>
     */
    private function validCreatePayload(int $typeId, int $supplierId, string $invoiceNumber): array
    {
        $date = now()->toDateString();

        return [
            '_token' => csrf_token(),
            'invoice_number' => $invoiceNumber,
            'faktur_no' => null,
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => (string) $supplierId,
            'po_no' => null,
            'receive_project' => '',
            'invoice_project' => '',
            'payment_project' => '001H',
            'currency' => 'IDR',
            'amount' => '8502600.00',
            'type_id' => (string) $typeId,
            'payment_date' => null,
            'remarks' => null,
            'cur_loc' => '001HFIN',
            'sap_doc' => null,
            'import_uuid' => '',
            'additional_document_ids' => [],
        ];
    }

    public function test_store_rejects_consignment_invoice_without_gl_account(): void
    {
        [$user, , $consignmentType, $supplier] = $this->seedActors();

        $payload = $this->validCreatePayload($consignmentType->id, $supplier->id, 'INV-CON-NO-GL');
        $payload['import_line_items'] = [
            [
                'description' => 'Rubber spring',
                'quantity' => '1',
                'unit_price' => '7660000',
                'amount' => '7660000',
            ],
        ];

        $response = $this->actingAs($user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasErrors(['gl_account']);
        $this->assertSame(0, Invoice::query()->where('invoice_number', 'INV-CON-NO-GL')->count());
    }

    public function test_store_rejects_consignment_invoice_without_line_items(): void
    {
        [$user, , $consignmentType, $supplier] = $this->seedActors();

        $payload = $this->validCreatePayload($consignmentType->id, $supplier->id, 'INV-CON-NO-LINES');
        $payload['gl_account'] = '51106011';

        $response = $this->actingAs($user)->post(route('invoices.store'), $payload);

        $response->assertSessionHasErrors(['import_line_items']);
        $this->assertSame(0, Invoice::query()->where('invoice_number', 'INV-CON-NO-LINES')->count());
    }

    public function test_store_persists_consignment_lines_without_import_uuid(): void
    {
        [$user, , $consignmentType, $supplier] = $this->seedActors();

        $payload = $this->validCreatePayload($consignmentType->id, $supplier->id, 'INV-CON-OK');
        $payload['gl_account'] = '51106011';
        $payload['import_line_items'] = [
            [
                'description' => 'Rubber spring',
                'quantity' => '1',
                'unit_price' => '7660000',
                'amount' => '7660000',
            ],
        ];

        $response = $this->actingAs($user)->post(route('invoices.store'), $payload);
        $response->assertRedirect(route('invoices.index'));

        $invoice = Invoice::query()->where('invoice_number', 'INV-CON-OK')->firstOrFail();
        $this->assertTrue($invoice->isConsignment());
        $this->assertSame('51106011', $invoice->gl_account);
        $this->assertSame(1, $invoice->lineDetails()->count());
        $this->assertSame('Rubber spring', $invoice->lineDetails->first()->description);
        $this->assertSame('user', $invoice->lineDetails->first()->source);
    }

    public function test_non_consignment_store_does_not_require_gl_account_or_lines(): void
    {
        [$user, $itemType, , $supplier] = $this->seedActors();

        $payload = $this->validCreatePayload($itemType->id, $supplier->id, 'INV-ITEM-OK');

        $response = $this->actingAs($user)->post(route('invoices.store'), $payload);
        $response->assertRedirect(route('invoices.index'));

        $invoice = Invoice::query()->where('invoice_number', 'INV-ITEM-OK')->firstOrFail();
        $this->assertFalse($invoice->isConsignment());
        $this->assertNull($invoice->gl_account);
        $this->assertSame(0, $invoice->lineDetails()->count());
    }

    public function test_payload_builder_maps_consignment_lines_one_to_one(): void
    {
        [$user, , $consignmentType, $supplier] = $this->seedActors();
        $date = now()->toDateString();

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-CON-PAY',
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'currency' => 'IDR',
            'amount' => 8_502_600,
            'type_id' => $consignmentType->id,
            'gl_account' => '51106011',
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => '001HFIN',
            'invoice_project' => '001H',
        ]);

        InvoiceLineDetail::query()->create([
            'invoice_id' => $invoice->id,
            'line_no' => 1,
            'description' => 'Rubber spring',
            'quantity' => 1,
            'unit_price' => 7_660_000,
            'amount' => 7_660_000,
            'source' => 'user',
        ]);
        InvoiceLineDetail::query()->create([
            'invoice_id' => $invoice->id,
            'line_no' => 2,
            'description' => 'Hose',
            'quantity' => 3,
            'unit_price' => 755_500,
            'amount' => 2_266_500,
            'source' => 'user',
        ]);

        $invoice->refresh();
        $payload = (new SapApInvoicePayloadBuilder($invoice))->build();

        $this->assertCount(2, $payload['DocumentLines']);
        $this->assertSame('CONSIGNMENT', $payload['DocumentLines'][0]['ItemCode']);
        $this->assertSame('CONSIGNMENT', $payload['DocumentLines'][1]['ItemCode']);
        $this->assertSame('B111', $payload['DocumentLines'][0]['TaxCode']);
        $this->assertSame('51106011', $payload['DocumentLines'][0]['AccountCode']);
        $this->assertSame('40', $payload['DocumentLines'][0]['CostingCode']);
        $this->assertSame('40', $payload['DocumentLines'][1]['CostingCode']);
        $this->assertSame(1.0, $payload['DocumentLines'][0]['Quantity']);
        $this->assertSame(7660000.0, $payload['DocumentLines'][0]['UnitPrice']);
        $this->assertSame(3.0, $payload['DocumentLines'][1]['Quantity']);
        $this->assertSame(2266500.0, $payload['DocumentLines'][1]['LineTotal']);
        $this->assertArrayNotHasKey('BaseType', $payload['DocumentLines'][0]);
    }

    public function test_payload_builder_prefers_grpo_lines_for_consignment_invoice(): void
    {
        [$user, , $consignmentType, $supplier] = $this->seedActors();
        $date = now()->toDateString();

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-CON-GRPO',
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'po_no' => '260204997',
            'currency' => 'IDR',
            'amount' => 8_502_600,
            'type_id' => $consignmentType->id,
            'gl_account' => '51106011',
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => '001HFIN',
        ]);

        InvoiceLineDetail::query()->create([
            'invoice_id' => $invoice->id,
            'line_no' => 1,
            'description' => 'Should not be posted',
            'quantity' => 1,
            'unit_price' => 7_660_000,
            'amount' => 7_660_000,
            'source' => 'user',
        ]);

        $payload = (new SapApInvoicePayloadBuilder($invoice, [
            [
                'grpo_no' => '5001',
                'doc_entry' => 99,
                'base_line' => 0,
                'item_code' => 'ITEM-A',
                'quantity' => 10,
                'unit_price' => 50000,
            ],
        ]))->build();

        $this->assertCount(1, $payload['DocumentLines']);
        $this->assertSame('ITEM-A', $payload['DocumentLines'][0]['ItemCode']);
        $this->assertSame(20, $payload['DocumentLines'][0]['BaseType']);
        $this->assertSame(99, $payload['DocumentLines'][0]['BaseEntry']);
        $this->assertArrayNotHasKey('AccountCode', $payload['DocumentLines'][0]);
        $this->assertSame('B111', $payload['DocumentLines'][0]['TaxCode']);
    }

    public function test_update_rejects_consignment_without_line_items(): void
    {
        [$user, $itemType, $consignmentType, $supplier] = $this->seedActors();
        $date = now()->toDateString();

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-CON-UPD',
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'currency' => 'IDR',
            'amount' => 100,
            'type_id' => $itemType->id,
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => '001HFIN',
        ]);

        $response = $this->actingAs($user)->put(route('invoices.update', $invoice), [
            'invoice_number' => 'INV-CON-UPD',
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'currency' => 'IDR',
            'amount' => 100,
            'type_id' => $consignmentType->id,
            'gl_account' => '51106011',
            'cur_loc' => '001HFIN',
            'status' => 'open',
        ]);

        $response->assertSessionHasErrors(['import_line_items']);
        $this->assertFalse($invoice->fresh()->isConsignment());
    }
}
