<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DomainAssistantDataService;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainAssistantInvoiceSupplierFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_invoices_filters_by_supplier_query(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
            'username' => 'invfiltertest',
        ]);
        $user->assignRole('admin');

        $typeId = InvoiceType::query()->firstOrFail()->id;

        $supplierMatch = Supplier::query()->create([
            'sap_code' => 'V-CSJ',
            'name' => 'PT Cahaya Sarange Jaya',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $supplierOther = Supplier::query()->create([
            'sap_code' => 'V-OTH',
            'name' => 'Other Vendor',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $date = now()->toDateString();

        Invoice::query()->create([
            'invoice_number' => 'INV-A1',
            'faktur_no' => null,
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplierMatch->id,
            'currency' => 'IDR',
            'amount' => 1000,
            'type_id' => $typeId,
            'created_by' => $user->id,
            'status' => 'open',
        ]);

        Invoice::query()->create([
            'invoice_number' => 'INV-B1',
            'faktur_no' => null,
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplierOther->id,
            'currency' => 'IDR',
            'amount' => 2000,
            'type_id' => $typeId,
            'created_by' => $user->id,
            'status' => 'open',
        ]);

        $service = app(DomainAssistantDataService::class);

        $unfiltered = $service->searchInvoices($user, false, null, 20, null, null, null, null);
        $this->assertCount(2, $unfiltered);

        $filtered = $service->searchInvoices($user, false, null, 20, null, null, 'Cahaya Sarange', null);
        $this->assertCount(1, $filtered);
        $this->assertSame('INV-A1', $filtered[0]['invoice_number']);
        $this->assertSame('PT Cahaya Sarange Jaya', $filtered[0]['supplier']);
    }

    public function test_search_invoices_filters_by_invoice_number_query(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
            'username' => 'invnumquery',
        ]);
        $user->assignRole('admin');

        $typeId = InvoiceType::query()->firstOrFail()->id;

        $supplier = Supplier::query()->create([
            'sap_code' => 'V-NUM',
            'name' => 'HEXINDO TEST',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $date = now()->toDateString();

        Invoice::query()->create([
            'invoice_number' => 'SG410-00000129',
            'faktur_no' => '04002600108345076',
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'currency' => 'IDR',
            'amount' => 100,
            'type_id' => $typeId,
            'created_by' => $user->id,
            'status' => 'open',
        ]);

        Invoice::query()->create([
            'invoice_number' => 'OTHER-INV-1',
            'faktur_no' => null,
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'currency' => 'IDR',
            'amount' => 200,
            'type_id' => $typeId,
            'created_by' => $user->id,
            'status' => 'open',
        ]);

        $service = app(DomainAssistantDataService::class);

        $byInvoice = $service->searchInvoices($user, false, null, 20, null, null, null, '00000129');
        $this->assertCount(1, $byInvoice);
        $this->assertSame('SG410-00000129', $byInvoice[0]['invoice_number']);

        $byFaktur = $service->searchInvoices($user, false, null, 20, null, null, null, '08345076');
        $this->assertCount(1, $byFaktur);
        $this->assertSame('04002600108345076', $byFaktur[0]['faktur_no']);
    }
}
