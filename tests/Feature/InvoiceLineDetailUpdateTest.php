<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceLineDetail;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceLineDetailUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_invoice_line_detail(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');

        $typeId = InvoiceType::query()->firstOrFail()->id;
        $supplier = Supplier::query()->create([
            'sap_code' => 'V-LD',
            'name' => 'Line Detail Vendor',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $date = now()->toDateString();
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-LD-1',
            'faktur_no' => null,
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'currency' => 'IDR',
            'amount' => 1_000_000,
            'type_id' => $typeId,
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => 'LOC1',
        ]);

        $line = InvoiceLineDetail::query()->create([
            'invoice_id' => $invoice->id,
            'line_no' => 1,
            'description' => 'Original',
            'quantity' => 10,
            'unit_price' => 100,
            'amount' => 1000,
            'source' => 'import',
        ]);

        $response = $this->actingAs($user)->patchJson(route('invoices.line-details.update', [
            'invoice' => $invoice->id,
            'lineDetail' => $line->id,
        ]), [
            'description' => 'Updated desc',
            'quantity' => '2.5',
            'unit_price' => '400',
            'amount' => '1000',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $line->refresh();
        $this->assertSame('Updated desc', $line->description);
        $this->assertSame('2.5000', $line->quantity);
        $this->assertSame('400.0000', $line->unit_price);
        $this->assertSame('1000.00', $line->amount);
        $this->assertSame('adjusted', $line->source);
    }

    public function test_update_returns_404_when_line_belongs_to_other_invoice(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');

        $typeId = InvoiceType::query()->firstOrFail()->id;
        $supplier = Supplier::query()->create([
            'sap_code' => 'V-LD2',
            'name' => 'Vendor 2',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $date = now()->toDateString();
        $invoiceA = Invoice::query()->create([
            'invoice_number' => 'INV-LD-A',
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'currency' => 'IDR',
            'amount' => 100,
            'type_id' => $typeId,
            'created_by' => $user->id,
            'status' => 'open',
        ]);
        $invoiceB = Invoice::query()->create([
            'invoice_number' => 'INV-LD-B',
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'currency' => 'IDR',
            'amount' => 200,
            'type_id' => $typeId,
            'created_by' => $user->id,
            'status' => 'open',
        ]);

        $lineOnB = InvoiceLineDetail::query()->create([
            'invoice_id' => $invoiceB->id,
            'line_no' => 1,
            'description' => 'On B',
            'quantity' => null,
            'unit_price' => null,
            'amount' => 50,
            'source' => 'import',
        ]);

        $response = $this->actingAs($user)->patchJson(route('invoices.line-details.update', [
            'invoice' => $invoiceA->id,
            'lineDetail' => $lineOnB->id,
        ]), [
            'description' => 'Hack',
        ]);

        $response->assertNotFound();
    }
}
