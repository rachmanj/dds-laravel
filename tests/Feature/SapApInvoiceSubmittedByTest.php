<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SapApInvoicePayloadBuilder;
use Carbon\Carbon;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SapApInvoiceSubmittedByTest extends TestCase
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
            'po_no' => null,
            'currency' => 'IDR',
            'amount' => 1_000_000,
            'type_id' => $typeId,
            'created_by' => $user->id,
            'status' => 'sap',
            'cur_loc' => 'LOC1',
        ], $overrides));
    }

    public function test_payload_includes_u_mis_submitted_with_username_and_sap_submitted_at(): void
    {
        $creator = User::factory()->create(['is_active' => true]);
        $submitter = User::factory()->create([
            'is_active' => true,
            'username' => 'elma',
            'name' => 'Elma Full Name',
        ]);

        $submittedAt = Carbon::parse('2026-09-25 11:41:00', config('app.timezone'));

        $invoice = $this->createSapReadyInvoice($creator, [
            'sap_submitted_by_user_id' => $submitter->id,
            'sap_submitted_at' => $submittedAt,
        ]);
        $invoice->load('sapSubmitter');

        $builder = new SapApInvoicePayloadBuilder($invoice);
        $payload = $builder->build();

        $this->assertSame('elma 25/09/2026 11:41', $payload['U_MIS_Submitted']);
        $this->assertSame('Elma Full Name', $payload['U_MIS_Created']);
    }

    public function test_u_mis_submitted_uses_now_when_sap_submitted_at_is_null(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-25 11:41:00', config('app.timezone')));

        try {
            $creator = User::factory()->create(['is_active' => true]);
            $submitter = User::factory()->create([
                'is_active' => true,
                'username' => 'elma',
            ]);

            $invoice = $this->createSapReadyInvoice($creator, [
                'sap_submitted_by_user_id' => $submitter->id,
                'sap_submitted_at' => null,
            ]);
            $invoice->load('sapSubmitter');

            $builder = new SapApInvoicePayloadBuilder($invoice);
            $payload = $builder->build();

            $this->assertSame('elma 25/09/2026 11:41', $payload['U_MIS_Submitted']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_u_mis_submitted_truncates_long_username_to_fit_thirty_characters(): void
    {
        $creator = User::factory()->create(['is_active' => true]);
        $submitter = User::factory()->create([
            'is_active' => true,
            'username' => 'verylongusernamethatexceedslimit',
        ]);

        $submittedAt = Carbon::parse('2026-09-25 11:41:00', config('app.timezone'));

        $invoice = $this->createSapReadyInvoice($creator, [
            'sap_submitted_by_user_id' => $submitter->id,
            'sap_submitted_at' => $submittedAt,
        ]);
        $invoice->load('sapSubmitter');

        $builder = new SapApInvoicePayloadBuilder($invoice);
        $value = $builder->build()['U_MIS_Submitted'];

        $this->assertLessThanOrEqual(30, strlen($value));
        $this->assertStringEndsWith('25/09/2026 11:41', $value);
        $this->assertSame('verylongusern 25/09/2026 11:41', $value);
    }

    public function test_build_faktur_patch_fields_includes_u_mis_submitted(): void
    {
        $creator = User::factory()->create(['is_active' => true]);
        $submitter = User::factory()->create([
            'is_active' => true,
            'username' => 'elma',
        ]);

        $submittedAt = Carbon::parse('2026-09-25 11:41:00', config('app.timezone'));

        $invoice = $this->createSapReadyInvoice($creator, [
            'sap_submitted_by_user_id' => $submitter->id,
            'sap_submitted_at' => $submittedAt,
        ]);
        $invoice->load('sapSubmitter');

        $builder = new SapApInvoicePayloadBuilder($invoice);
        $patchFields = $builder->buildFakturPatchFields();

        $this->assertArrayHasKey('U_MIS_Submitted', $patchFields);
        $this->assertSame('elma 25/09/2026 11:41', $patchFields['U_MIS_Submitted']);
    }

    public function test_u_mis_submitted_omitted_when_submitter_username_is_empty(): void
    {
        $creator = User::factory()->create(['is_active' => true]);
        $submitter = User::factory()->create([
            'is_active' => true,
            'username' => null,
            'name' => 'Known Name Only',
        ]);

        $invoice = $this->createSapReadyInvoice($creator, [
            'sap_submitted_by_user_id' => $submitter->id,
            'sap_submitted_at' => now(),
        ]);
        $invoice->load('sapSubmitter');

        $builder = new SapApInvoicePayloadBuilder($invoice);
        $payload = $builder->build();
        $patchFields = $builder->buildFakturPatchFields();

        $this->assertArrayNotHasKey('U_MIS_Submitted', $payload);
        $this->assertArrayNotHasKey('U_MIS_Submitted', $patchFields);
        $this->assertSame('Known Name Only', $payload['U_MIS_Created']);
    }

    public function test_u_mis_submitted_omitted_when_sap_submitter_is_not_set(): void
    {
        $creator = User::factory()->create(['is_active' => true, 'username' => 'creator']);

        $invoice = $this->createSapReadyInvoice($creator, [
            'sap_submitted_by_user_id' => null,
            'sap_submitted_at' => null,
        ]);

        $builder = new SapApInvoicePayloadBuilder($invoice);
        $payload = $builder->build();

        $this->assertArrayNotHasKey('U_MIS_Submitted', $payload);
    }
}
