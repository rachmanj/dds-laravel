<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DomainAssistantDataService;
use App\Support\InvoiceListScope;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceListLocationFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: int}
     */
    private function createAccountingUser(): array
    {
        $department = Department::query()->create([
            'name' => 'Accounting',
            'project' => '001H',
            'location_code' => '000HACC',
            'akronim' => 'ACC',
        ]);

        $user = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
        ]);
        $user->assignRole('accounting');

        $typeId = InvoiceType::query()->firstOrFail()->id;

        return [$user, $typeId];
    }

    private function createInvoice(User $user, int $typeId, string $invoiceNumber, string $curLoc): Invoice
    {
        $supplier = Supplier::query()->create([
            'sap_code' => 'V-'.$invoiceNumber,
            'name' => 'Supplier '.$invoiceNumber,
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $date = now()->toDateString();

        return Invoice::query()->create([
            'invoice_number' => $invoiceNumber,
            'faktur_no' => null,
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'currency' => 'IDR',
            'amount' => 1000,
            'type_id' => $typeId,
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => $curLoc,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
    }

    public function test_accounting_user_list_defaults_to_department_location(): void
    {
        [$user, $typeId] = $this->createAccountingUser();

        $this->createInvoice($user, $typeId, 'INV-ACC-1', '000HACC');
        $this->createInvoice($user, $typeId, 'INV-FIN-1', '001HFIN');

        $service = app(DomainAssistantDataService::class);
        $visible = $service->searchInvoices($user, false, null, 20, null, null, null, null);

        $this->assertCount(1, $visible);
        $this->assertSame('INV-ACC-1', $visible[0]['invoice_number']);
    }

    public function test_accounting_user_list_expands_when_show_all_enabled(): void
    {
        [$user, $typeId] = $this->createAccountingUser();

        $this->createInvoice($user, $typeId, 'INV-ACC-2', '000HACC');
        $this->createInvoice($user, $typeId, 'INV-FIN-2', '001HFIN');

        $service = app(DomainAssistantDataService::class);
        $visible = $service->searchInvoices($user, true, null, 20, null, null, null, null);

        $this->assertCount(2, $visible);
    }

    public function test_accounting_user_can_view_cross_department_invoice_show_page(): void
    {
        [$user, $typeId] = $this->createAccountingUser();
        $invoice = $this->createInvoice($user, $typeId, 'INV-FIN-SHOW', '001HFIN');

        $response = $this->actingAs($user)->get(route('invoices.show', $invoice));

        $response->assertOk();
        $response->assertSee('INV-FIN-SHOW');
    }

    public function test_invoice_list_scope_skips_filter_only_for_admin_or_show_all(): void
    {
        [$user, $typeId] = $this->createAccountingUser();

        $this->assertFalse(InvoiceListScope::shouldSkipLocationFilter($user, false));
        $this->assertTrue(InvoiceListScope::shouldSkipLocationFilter($user, true));

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        $this->assertTrue(InvoiceListScope::shouldSkipLocationFilter($admin, false));
    }

    public function test_invoice_data_endpoint_respects_location_filter_for_accounting_user(): void
    {
        [$user, $typeId] = $this->createAccountingUser();

        $this->createInvoice($user, $typeId, 'INV-ACC-DATA', '000HACC');
        $this->createInvoice($user, $typeId, 'INV-FIN-DATA', '001HFIN');

        $filtered = $this->actingAs($user)->getJson(route('invoices.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'show_all' => 0,
        ]));

        $filtered->assertOk();
        $filtered->assertJsonPath('recordsTotal', 1);

        $expanded = $this->actingAs($user)->getJson(route('invoices.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'show_all' => 1,
        ]));

        $expanded->assertOk();
        $expanded->assertJsonPath('recordsTotal', 2);
    }
}
