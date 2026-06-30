<?php

namespace Tests\Feature;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\AdditionalDocumentTypeSeeder;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributionCheckLinkedDocumentsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Department, 2: int, 3: int}
     */
    private function seedBasics(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
        $this->seed(AdditionalDocumentTypeSeeder::class);

        $department = Department::query()->create([
            'name' => 'Distribution Test Dept',
            'project' => '001H',
            'location_code' => '000HACC',
            'akronim' => 'DTST',
        ]);

        $user = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
        ]);
        $user->assignRole('admin');

        $additionalDocumentTypeId = AdditionalDocumentType::query()->firstOrFail()->id;
        $invoiceTypeId = InvoiceType::query()->firstOrFail()->id;

        return [$user, $department, $additionalDocumentTypeId, $invoiceTypeId];
    }

    public function test_check_linked_documents_returns_only_invoice_attached_documents(): void
    {
        [$user, $department, $additionalDocumentTypeId, $invoiceTypeId] = $this->seedBasics();
        $date = now()->toDateString();

        $supplier = Supplier::query()->create([
            'sap_code' => 'DISTTEST01',
            'name' => 'Distribution Test Supplier',
            'type' => 'vendor',
            'payment_project' => '001H',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-LINK-001',
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'type_id' => $invoiceTypeId,
            'po_no' => 'PO-SHARED-001',
            'amount' => 100000,
            'cur_loc' => $department->location_code,
            'created_by' => $user->id,
            'distribution_status' => 'available',
        ]);

        $attachedDocument = AdditionalDocument::query()->create([
            'type_id' => $additionalDocumentTypeId,
            'document_number' => 'ITO-ATTACHED-001',
            'document_date' => $date,
            'po_no' => 'PO-SHARED-001',
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => $department->location_code,
            'distribution_status' => 'available',
        ]);

        AdditionalDocument::query()->create([
            'type_id' => $additionalDocumentTypeId,
            'document_number' => 'ITO-PO-ONLY-001',
            'document_date' => $date,
            'po_no' => 'PO-SHARED-001',
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => $department->location_code,
            'distribution_status' => 'available',
        ]);

        $invoice->additionalDocuments()->attach($attachedDocument->id);

        $response = $this->actingAs($user)->postJson(
            route('distributions.check-linked-documents'),
            ['invoice_ids' => [$invoice->id]]
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'linked_documents')
            ->assertJsonPath('linked_documents.0.document_number', 'ITO-ATTACHED-001');
    }
}
