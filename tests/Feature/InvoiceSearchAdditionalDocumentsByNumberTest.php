<?php

namespace Tests\Feature;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\AdditionalDocumentTypeSeeder;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSearchAdditionalDocumentsByNumberTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: int, 2: int}
     */
    private function seedBasics(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
        $this->seed(AdditionalDocumentTypeSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');

        $typeId = AdditionalDocumentType::query()->firstOrFail()->id;
        $invoiceTypeId = InvoiceType::query()->firstOrFail()->id;

        return [$user, $typeId, $invoiceTypeId];
    }

    public function test_search_returns_documents_matching_document_number_fragment(): void
    {
        [$user, $typeId] = $this->seedBasics();
        $date = now()->toDateString();

        AdditionalDocument::query()->create([
            'type_id' => $typeId,
            'document_number' => 'ITO-2024-001',
            'document_date' => $date,
            'po_no' => 'PO-100',
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => '000HLOG',
        ]);

        AdditionalDocument::query()->create([
            'type_id' => $typeId,
            'document_number' => 'ITO-2024-002',
            'document_date' => $date,
            'po_no' => null,
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => '000HACC',
        ]);

        AdditionalDocument::query()->create([
            'type_id' => $typeId,
            'document_number' => 'BAST-99',
            'document_date' => $date,
            'po_no' => 'PO-200',
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => '000HLOG',
        ]);

        $response = $this->actingAs($user)->postJson(
            route('invoices.search-additional-documents-by-number'),
            ['document_number' => 'ITO-2024']
        );

        $response->assertOk()
            ->assertJsonPath('success', true);

        $numbers = collect($response->json('documents'))->pluck('document_number')->all();
        $this->assertEqualsCanonicalizing(['ITO-2024-001', 'ITO-2024-002'], $numbers);
    }

    public function test_search_includes_documents_without_po_number(): void
    {
        [$user, $typeId] = $this->seedBasics();
        $date = now()->toDateString();

        AdditionalDocument::query()->create([
            'type_id' => $typeId,
            'document_number' => 'NO-PO-DOC-1',
            'document_date' => $date,
            'po_no' => null,
            'created_by' => $user->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($user)->postJson(
            route('invoices.search-additional-documents-by-number'),
            ['document_number' => 'NO-PO']
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('documents.0.document_number', 'NO-PO-DOC-1');
    }

    public function test_search_requires_document_number(): void
    {
        [$user] = $this->seedBasics();

        $response = $this->actingAs($user)->postJson(
            route('invoices.search-additional-documents-by-number'),
            []
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['document_number']);
    }

    public function test_search_marks_documents_linked_to_current_invoice(): void
    {
        [$user, $typeId, $invoiceTypeId] = $this->seedBasics();
        $date = now()->toDateString();

        $supplier = Supplier::query()->create([
            'sap_code' => 'V-ADOC',
            'name' => 'Additional Doc Vendor',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-LINK-ADOC',
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'currency' => 'IDR',
            'amount' => 100,
            'type_id' => $invoiceTypeId,
            'created_by' => $user->id,
            'status' => 'open',
        ]);

        $linkedDoc = AdditionalDocument::query()->create([
            'type_id' => $typeId,
            'document_number' => 'LINKED-ITO-1',
            'document_date' => $date,
            'created_by' => $user->id,
            'status' => 'open',
        ]);

        $invoice->additionalDocuments()->attach($linkedDoc->id);

        $response = $this->actingAs($user)->postJson(
            route('invoices.search-additional-documents-by-number'),
            [
                'document_number' => 'LINKED-ITO',
                'current_invoice_id' => $invoice->id,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('documents.0.id', $linkedDoc->id)
            ->assertJsonPath('documents.0.is_linked_to_current', true);
    }
}
