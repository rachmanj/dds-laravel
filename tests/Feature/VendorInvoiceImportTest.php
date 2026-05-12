<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceLineDetail;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VendorInvoiceImportTest extends TestCase
{
    use RefreshDatabase;

    private function configureVendorApi(int $typeId): void
    {
        config([
            'vendor_api.vendors' => [
                'VCASJIDR01' => [
                    'base_url' => 'https://vendor.test',
                    'token' => 'test-token',
                    'type_id' => $typeId,
                    'cur_loc' => '000HPROC',
                ],
            ],
        ]);
    }

    private function seedAdminAndSupplier(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
        $typeId = InvoiceType::query()->firstOrFail()->id;

        $admin = User::factory()->create(['is_active' => true, 'username' => 'vendorimportadmin']);
        $admin->assignRole('admin');

        $department = Department::query()->create([
            'name' => 'Import Test Dept',
            'project' => '001H',
            'location_code' => 'TESTVIMPRT',
            'akronim' => 'TVIMPRT',
        ]);
        $admin->update(['department_id' => $department->id]);

        $supplier = Supplier::query()->create([
            'sap_code' => 'VCASJIDR01',
            'name' => 'CAHAYA SARANGE JAYA, PT',
            'type' => 'vendor',
            'payment_project' => '001H',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $this->configureVendorApi($typeId);

        return [$admin, $supplier, $typeId];
    }

    private function detailPayload(string $invoiceNo): array
    {
        return [
            'data' => [
                'invoice_no' => $invoiceNo,
                'date' => '2026-05-05',
                'due_date' => '2026-06-04',
                'terms_days' => 30,
                'status' => 'posted',
                'total_amount' => 53391,
                'reference_no' => '260201884 - 022C',
                'description' => 'From DO 71260700231',
                'posted_at' => '2026-04-30T18:23:27+08:00',
                'exchange_rate' => 1,
                'currency' => [
                    'code' => 'IDR',
                    'symbol' => 'Rp',
                    'name' => 'Indonesian Rupiah',
                ],
                'lines' => [
                    [
                        'item' => 'CON000011 Cable Ties',
                        'description' => 'Cable Ties',
                        'qty' => 2,
                        'unit_price' => 14800,
                        'discount' => 0,
                        'total' => 32856,
                    ],
                ],
            ],
        ];
    }

    public function test_guest_is_redirected_from_vendor_invoice_import(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $supplier = Supplier::query()->create([
            'sap_code' => 'VCASJIDR01',
            'name' => 'V',
            'type' => 'vendor',
            'payment_project' => '001H',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $this->configureVendorApi(InvoiceType::query()->firstOrFail()->id);

        $this->get(route('admin.suppliers.vendor-invoices.index', $supplier))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_supplier_permissions_cannot_access_vendor_invoice_import(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $supplier = Supplier::query()->create([
            'sap_code' => 'VCASJIDR01',
            'name' => 'V',
            'type' => 'vendor',
            'payment_project' => '001H',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $this->configureVendorApi(InvoiceType::query()->firstOrFail()->id);

        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->assignRole('logistic');

        $this->actingAs($viewer)->get(route('admin.suppliers.vendor-invoices.index', $supplier))
            ->assertForbidden();
    }

    public function test_accounting_user_can_access_suppliers_index_and_vendor_invoice_import_screen(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
        $accounting = User::factory()->create(['is_active' => true, 'username' => 'elma']);
        $accounting->assignRole('accounting');

        $supplier = Supplier::query()->create([
            'sap_code' => 'VCASJIDR01',
            'name' => 'V',
            'type' => 'vendor',
            'payment_project' => '001H',
            'is_active' => true,
            'created_by' => $accounting->id,
        ]);
        $this->configureVendorApi(InvoiceType::query()->firstOrFail()->id);

        $this->actingAs($accounting)->get(route('admin.suppliers.index'))
            ->assertOk();

        $this->actingAs($accounting)->get(route('admin.suppliers.vendor-invoices.index', $supplier))
            ->assertOk();
    }

    public function test_accounting_user_can_post_vendor_invoice_lookup(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
        $accounting = User::factory()->create(['is_active' => true]);
        $accounting->assignRole('accounting');

        $supplier = Supplier::query()->create([
            'sap_code' => 'VCASJIDR01',
            'name' => 'V',
            'type' => 'vendor',
            'payment_project' => '001H',
            'is_active' => true,
            'created_by' => $accounting->id,
        ]);
        $this->configureVendorApi(InvoiceType::query()->firstOrFail()->id);

        $invoiceNo = '71260800181';

        Http::fake([
            'https://vendor.test/api/v1/invoices/'.$invoiceNo => Http::response($this->detailPayload($invoiceNo), 200),
        ]);

        $response = $this->actingAs($accounting)->from(route('admin.suppliers.vendor-invoices.index', $supplier))
            ->post(route('admin.suppliers.vendor-invoices.lookup', $supplier), [
                'invoice_numbers' => $invoiceNo,
            ]);

        $response->assertOk();
        $response->assertSee('Preview', false);
    }

    public function test_returns_404_when_supplier_is_not_configured_for_vendor_api(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');
        $supplier = Supplier::query()->create([
            'sap_code' => 'NOT_VEND_API',
            'name' => 'Other',
            'type' => 'vendor',
            'payment_project' => '001H',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('admin.suppliers.vendor-invoices.index', $supplier))
            ->assertNotFound();
    }

    public function test_lookup_shows_new_status_when_invoice_exists_on_api_and_not_in_db(): void
    {
        [$admin, $supplier] = $this->seedAdminAndSupplier();
        $invoiceNo = '71260800178';

        Http::fake([
            'https://vendor.test/api/v1/invoices/'.$invoiceNo => Http::response($this->detailPayload($invoiceNo), 200),
        ]);

        $response = $this->actingAs($admin)->from(route('admin.suppliers.vendor-invoices.index', $supplier))
            ->post(route('admin.suppliers.vendor-invoices.lookup', $supplier), [
                'invoice_numbers' => $invoiceNo,
            ]);

        $response->assertOk();
        $response->assertSee('Preview', false);
        $response->assertSee('New', false);
        $response->assertDontSee('Already in DB', false);
    }

    public function test_lookup_shows_duplicate_when_invoice_already_in_local_db(): void
    {
        [$admin, $supplier, $typeId] = $this->seedAdminAndSupplier();
        $invoiceNo = '71260800178';

        Invoice::query()->create([
            'invoice_number' => $invoiceNo,
            'faktur_no' => null,
            'invoice_date' => '2026-05-01',
            'receive_date' => '2026-05-01',
            'supplier_id' => $supplier->id,
            'po_no' => null,
            'receive_project' => null,
            'invoice_project' => null,
            'payment_project' => '001H',
            'currency' => 'IDR',
            'amount' => 100,
            'type_id' => $typeId,
            'payment_date' => null,
            'remarks' => null,
            'cur_loc' => '000HPROC',
            'status' => 'open',
            'created_by' => $admin->id,
        ]);

        Http::fake([
            'https://vendor.test/api/v1/invoices/'.$invoiceNo => Http::response($this->detailPayload($invoiceNo), 200),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.suppliers.vendor-invoices.lookup', $supplier), [
            'invoice_numbers' => $invoiceNo,
        ]);

        $response->assertOk();
        $response->assertSee('Already in DB', false);
    }

    public function test_lookup_shows_not_found_when_vendor_api_returns_404(): void
    {
        [$admin, $supplier] = $this->seedAdminAndSupplier();
        $invoiceNo = 'MISSING123';

        Http::fake([
            'https://vendor.test/api/v1/invoices/MISSING123' => Http::response([], 404),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.suppliers.vendor-invoices.lookup', $supplier), [
            'invoice_numbers' => $invoiceNo,
        ]);

        $response->assertOk();
        $response->assertSee('Not found', false);
    }

    public function test_import_creates_invoice_and_line_details(): void
    {
        [$admin, $supplier] = $this->seedAdminAndSupplier();
        $invoiceNo = '71260800179';

        Http::fake([
            'https://vendor.test/api/v1/invoices/'.$invoiceNo => Http::response($this->detailPayload($invoiceNo), 200),
        ]);

        $this->actingAs($admin)->post(route('admin.suppliers.vendor-invoices.import', $supplier), [
            'invoice_nos' => [$invoiceNo],
        ])
            ->assertRedirect(route('admin.suppliers.vendor-invoices.index', $supplier))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => $invoiceNo,
            'supplier_id' => $supplier->id,
            'amount' => '53391.00',
            'cur_loc' => 'TESTVIMPRT',
        ]);

        $invoice = Invoice::query()->where('invoice_number', $invoiceNo)->firstOrFail();
        $this->assertDatabaseHas('invoice_line_details', [
            'invoice_id' => $invoice->id,
            'line_no' => 1,
            'source' => 'vendor_api',
        ]);
        $this->assertSame(1, InvoiceLineDetail::query()->where('invoice_id', $invoice->id)->count());
    }

    public function test_import_skips_when_invoice_already_exists(): void
    {
        [$admin, $supplier, $typeId] = $this->seedAdminAndSupplier();
        $invoiceNo = '71260800180';

        Invoice::query()->create([
            'invoice_number' => $invoiceNo,
            'faktur_no' => null,
            'invoice_date' => '2026-05-01',
            'receive_date' => '2026-05-01',
            'supplier_id' => $supplier->id,
            'po_no' => null,
            'receive_project' => null,
            'invoice_project' => null,
            'payment_project' => '001H',
            'currency' => 'IDR',
            'amount' => 100,
            'type_id' => $typeId,
            'payment_date' => null,
            'remarks' => null,
            'cur_loc' => '000HPROC',
            'status' => 'open',
            'created_by' => $admin->id,
        ]);

        Http::fake([
            'https://vendor.test/api/v1/invoices/'.$invoiceNo => Http::response($this->detailPayload($invoiceNo), 200),
        ]);

        $this->actingAs($admin)->post(route('admin.suppliers.vendor-invoices.import', $supplier), [
            'invoice_nos' => [$invoiceNo],
        ])
            ->assertRedirect(route('admin.suppliers.vendor-invoices.index', $supplier));

        $this->assertSame(1, Invoice::query()->where('invoice_number', $invoiceNo)->count());
    }

    public function test_import_uses_config_cur_loc_when_user_has_no_department(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
        $typeId = InvoiceType::query()->firstOrFail()->id;

        $admin = User::factory()->create([
            'is_active' => true,
            'username' => 'nodeptvendorimport',
            'department_id' => null,
        ]);
        $admin->assignRole('admin');

        $supplier = Supplier::query()->create([
            'sap_code' => 'VCASJIDR01',
            'name' => 'Vendor No Dept',
            'type' => 'vendor',
            'payment_project' => '001H',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $this->configureVendorApi($typeId);

        $invoiceNo = '71260800999';

        Http::fake([
            'https://vendor.test/api/v1/invoices/'.$invoiceNo => Http::response($this->detailPayload($invoiceNo), 200),
        ]);

        $this->actingAs($admin)->post(route('admin.suppliers.vendor-invoices.import', $supplier), [
            'invoice_nos' => [$invoiceNo],
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => $invoiceNo,
            'cur_loc' => '000HPROC',
        ]);
    }
}
