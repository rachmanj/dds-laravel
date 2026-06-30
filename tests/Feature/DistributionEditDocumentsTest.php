<?php

namespace Tests\Feature;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Department;
use App\Models\Distribution;
use App\Models\DistributionDocument;
use App\Models\DistributionType;
use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\AdditionalDocumentTypeSeeder;
use Database\Seeders\DistributionTypeSeeder;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributionEditDocumentsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Department, 2: Department, 3: int, 4: int, 5: DistributionType}
     */
    private function seedBasics(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
        $this->seed(AdditionalDocumentTypeSeeder::class);
        $this->seed(DistributionTypeSeeder::class);

        $originDepartment = Department::query()->create([
            'name' => 'Origin Dept',
            'project' => '001H',
            'location_code' => '000HACC',
            'akronim' => 'ORIG',
        ]);

        $destinationDepartment = Department::query()->create([
            'name' => 'Destination Dept',
            'project' => '001H',
            'location_code' => '000HDEST',
            'akronim' => 'DEST',
        ]);

        $user = User::factory()->create([
            'is_active' => true,
            'department_id' => $originDepartment->id,
        ]);
        $user->assignRole('admin');

        $additionalDocumentTypeId = AdditionalDocumentType::query()->firstOrFail()->id;
        $invoiceTypeId = InvoiceType::query()->firstOrFail()->id;
        $distributionType = DistributionType::query()->firstOrFail();

        return [$user, $originDepartment, $destinationDepartment, $additionalDocumentTypeId, $invoiceTypeId, $distributionType];
    }

    public function test_edit_page_shows_standalone_additional_documents(): void
    {
        [
            $user,
            $originDepartment,
            $destinationDepartment,
            $additionalDocumentTypeId,
            $invoiceTypeId,
            $distributionType,
        ] = $this->seedBasics();

        $date = now()->toDateString();

        $supplier = Supplier::query()->create([
            'sap_code' => 'EDITDIST01',
            'name' => 'Edit Distribution Supplier',
            'type' => 'vendor',
            'payment_project' => '001H',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $distribution = Distribution::query()->create([
            'distribution_number' => '26/000HACC/DDS/TEST1',
            'type_id' => $distributionType->id,
            'origin_department_id' => $originDepartment->id,
            'destination_department_id' => $destinationDepartment->id,
            'document_type' => 'invoice',
            'created_by' => $user->id,
            'status' => 'draft',
            'year' => 2026,
            'sequence' => 1,
        ]);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-EDIT-001',
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'type_id' => $invoiceTypeId,
            'po_no' => 'PO-EDIT-001',
            'amount' => 100000,
            'cur_loc' => $originDepartment->location_code,
            'created_by' => $user->id,
            'distribution_status' => 'available',
        ]);

        $poOnlyDocument = AdditionalDocument::query()->create([
            'type_id' => $additionalDocumentTypeId,
            'document_number' => 'PO-ONLY-EDIT-001',
            'document_date' => $date,
            'po_no' => 'PO-EDIT-001',
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => $originDepartment->location_code,
            'distribution_status' => 'available',
        ]);

        DistributionDocument::query()->create([
            'distribution_id' => $distribution->id,
            'document_type' => Invoice::class,
            'document_id' => $invoice->id,
            'origin_cur_loc' => $originDepartment->location_code,
            'skip_verification' => false,
        ]);

        DistributionDocument::query()->create([
            'distribution_id' => $distribution->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $poOnlyDocument->id,
            'origin_cur_loc' => $originDepartment->location_code,
            'skip_verification' => false,
        ]);

        $response = $this->actingAs($user)->get(route('distributions.edit', $distribution));

        $response->assertOk()
            ->assertSee('PO-ONLY-EDIT-001')
            ->assertSee('Other Additional Documents');
    }

    public function test_user_can_remove_standalone_additional_document_from_invoice_distribution(): void
    {
        [
            $user,
            $originDepartment,
            $destinationDepartment,
            $additionalDocumentTypeId,
            $invoiceTypeId,
            $distributionType,
        ] = $this->seedBasics();

        $date = now()->toDateString();

        $supplier = Supplier::query()->create([
            'sap_code' => 'EDITDIST02',
            'name' => 'Edit Distribution Supplier 2',
            'type' => 'vendor',
            'payment_project' => '001H',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $distribution = Distribution::query()->create([
            'distribution_number' => '26/000HACC/DDS/TEST2',
            'type_id' => $distributionType->id,
            'origin_department_id' => $originDepartment->id,
            'destination_department_id' => $destinationDepartment->id,
            'document_type' => 'invoice',
            'created_by' => $user->id,
            'status' => 'draft',
            'year' => 2026,
            'sequence' => 2,
        ]);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-EDIT-002',
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'type_id' => $invoiceTypeId,
            'po_no' => 'PO-EDIT-002',
            'amount' => 100000,
            'cur_loc' => $originDepartment->location_code,
            'created_by' => $user->id,
            'distribution_status' => 'available',
        ]);

        $poOnlyDocument = AdditionalDocument::query()->create([
            'type_id' => $additionalDocumentTypeId,
            'document_number' => 'PO-ONLY-EDIT-002',
            'document_date' => $date,
            'po_no' => 'PO-EDIT-002',
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => $originDepartment->location_code,
            'distribution_status' => 'available',
        ]);

        DistributionDocument::query()->create([
            'distribution_id' => $distribution->id,
            'document_type' => Invoice::class,
            'document_id' => $invoice->id,
            'origin_cur_loc' => $originDepartment->location_code,
            'skip_verification' => false,
        ]);

        $distributionDocument = DistributionDocument::query()->create([
            'distribution_id' => $distribution->id,
            'document_type' => AdditionalDocument::class,
            'document_id' => $poOnlyDocument->id,
            'origin_cur_loc' => $originDepartment->location_code,
            'skip_verification' => false,
        ]);

        $response = $this->actingAs($user)->deleteJson(
            route('distributions.documents.detach', [$distribution, $distributionDocument])
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('documents.standalone_additional_documents', []);

        $this->assertDatabaseMissing('distribution_documents', [
            'id' => $distributionDocument->id,
        ]);
    }
}
