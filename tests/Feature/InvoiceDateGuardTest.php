<?php

namespace Tests\Feature;

use App\Models\InvoiceType;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\User;
use App\Support\InvoiceDateGuard;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceDateGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_returns_empty_for_normal_dates(): void
    {
        $this->assertSame([], InvoiceDateGuard::check('2026-08-01', '2026-09-01'));
        $this->assertSame([], InvoiceDateGuard::warn('2026-08-01', '2026-09-01'));
    }

    public function test_check_returns_empty_when_either_date_missing(): void
    {
        $this->assertSame([], InvoiceDateGuard::check(null, '2026-09-01'));
        $this->assertSame([], InvoiceDateGuard::check('2026-08-01', null));
        $this->assertSame([], InvoiceDateGuard::warn('', '2026-09-01'));
    }

    public function test_check_rejects_invoice_date_before_year_2000(): void
    {
        $messages = InvoiceDateGuard::check('1999-12-31', '2026-09-01');

        $this->assertNotEmpty($messages);
        $joined = implode(' ', $messages);
        $this->assertStringContainsString('31-12-1999', $joined);
        $this->assertStringContainsString('01-01-2000', $joined);
    }

    public function test_check_rejects_invoice_date_far_before_receive_date(): void
    {
        $messages = InvoiceDateGuard::check('2020-08-20', '2026-09-01');

        $this->assertNotEmpty($messages);
        $this->assertStringContainsString('20-08-2020', $messages[0]);
        $this->assertStringContainsString('01-09-2026', $messages[0]);
        $this->assertStringContainsString('Mohon periksa kembali tahun pada dokumen invoice', $messages[0]);
    }

    public function test_check_rejects_invoice_date_far_after_receive_date(): void
    {
        $messages = InvoiceDateGuard::check('2028-10-01', '2026-09-01');

        $this->assertCount(1, $messages);
        $this->assertStringContainsString('01-10-2028', $messages[0]);
        $this->assertStringContainsString('sesudah tanggal terima', $messages[0]);
    }

    public function test_warn_flags_invoice_date_more_than_six_months_before_receive(): void
    {
        $this->assertSame([], InvoiceDateGuard::check('2026-01-15', '2026-09-01'));

        $warnings = InvoiceDateGuard::warn('2026-01-15', '2026-09-01');

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('15-01-2026', $warnings[0]);
        $this->assertStringContainsString('Mohon periksa kembali tahun pada dokumen invoice', $warnings[0]);
    }

    public function test_store_rejects_invoice_date_that_violates_hard_rules(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
        $user->assignRole('admin');

        Project::query()->create([
            'code' => '001H',
            'owner' => 'HO',
            'location' => 'Jakarta',
            'is_active' => true,
        ]);

        $typeId = InvoiceType::query()->firstOrFail()->id;
        $supplier = Supplier::query()->create([
            'sap_code' => 'V-DATE',
            'name' => 'Date Guard Vendor',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('invoices.store'), [
            '_token' => csrf_token(),
            'invoice_number' => 'INV-DATE-GUARD-1',
            'faktur_no' => null,
            'invoice_date' => '2020-08-20',
            'receive_date' => '2026-09-01',
            'supplier_id' => (string) $supplier->id,
            'po_no' => null,
            'receive_project' => '',
            'invoice_project' => '',
            'payment_project' => '001H',
            'currency' => 'IDR',
            'amount' => '100.00',
            'type_id' => (string) $typeId,
            'payment_date' => null,
            'remarks' => null,
            'cur_loc' => '001HFIN',
            'sap_doc' => null,
            'additional_document_ids' => [],
        ]);

        $response->assertSessionHasErrors('invoice_date');
        $this->assertDatabaseMissing('invoices', ['invoice_number' => 'INV-DATE-GUARD-1']);
    }
}
