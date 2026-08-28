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

class DocumentReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
        $this->seed(AdditionalDocumentTypeSeeder::class);
    }

    private function createDepartment(string $name, string $locationCode): Department
    {
        return Department::query()->create([
            'name' => $name,
            'project' => '001H',
            'location_code' => $locationCode,
            'akronim' => strtoupper(substr($name, 0, 3)),
        ]);
    }

    private function createLogisticUser(string $locationCode): User
    {
        $department = $this->createDepartment('Logistics', $locationCode);

        $user = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
        ]);
        $user->assignRole('logistic');

        return $user;
    }

    private function createInvoice(User $user, string $invoiceNumber, string $curLoc): Invoice
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
            'type_id' => InvoiceType::query()->firstOrFail()->id,
            'created_by' => $user->id,
            'status' => 'open',
            'distribution_status' => 'available',
            'cur_loc' => $curLoc,
        ]);
    }

    private function createAdditionalDocument(User $user, string $documentNumber, string $curLoc): AdditionalDocument
    {
        $date = now()->toDateString();

        return AdditionalDocument::query()->create([
            'type_id' => AdditionalDocumentType::query()->firstOrFail()->id,
            'document_number' => $documentNumber,
            'document_date' => $date,
            'receive_date' => $date,
            'created_by' => $user->id,
            'status' => 'open',
            'distribution_status' => 'available',
            'cur_loc' => $curLoc,
        ]);
    }

    public function test_guest_cannot_access_document_report(): void
    {
        $this->get(route('document-report.invoices'))->assertRedirect(route('login'));
        $this->get(route('document-report.additional-documents'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_gets_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('document-report.invoices'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('document-report.additional-documents'))
            ->assertForbidden();
    }

    public function test_logistic_user_can_access_invoice_report_index(): void
    {
        $user = $this->createLogisticUser('000HLOG');

        $this->actingAs($user)
            ->get(route('document-report.invoices'))
            ->assertOk()
            ->assertSee('All Invoice Report');
    }

    public function test_logistic_user_can_access_additional_documents_report_index(): void
    {
        $user = $this->createLogisticUser('000HLOG');

        $this->actingAs($user)
            ->get(route('document-report.additional-documents'))
            ->assertOk()
            ->assertSee('All Additional Documents Report');
    }

    public function test_document_report_shows_invoices_across_all_locations(): void
    {
        $user = $this->createLogisticUser('000HLOG');

        $this->createInvoice($user, 'INV-LOG-1', '000HLOG');
        $this->createInvoice($user, 'INV-FIN-1', '001HFIN');

        $response = $this->actingAs($user)->getJson(route('document-report.invoices-data', [
            'draw' => 1,
            'start' => 0,
            'length' => 50,
        ]));

        $response->assertOk();
        $response->assertJsonPath('recordsTotal', 2);
    }

    public function test_invoice_filter_by_number_narrows_results(): void
    {
        $user = $this->createLogisticUser('000HLOG');

        $this->createInvoice($user, 'INV-MATCH', '000HLOG');
        $this->createInvoice($user, 'INV-OTHER', '001HFIN');

        $response = $this->actingAs($user)->getJson(route('document-report.invoices-data', [
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'search_invoice_number' => 'MATCH',
        ]));

        $response->assertOk();
        $response->assertJsonPath('recordsTotal', 1);
    }

    public function test_additional_documents_data_is_org_wide(): void
    {
        $user = $this->createLogisticUser('000HLOG');

        $this->createAdditionalDocument($user, 'ADOC-LOG', '000HLOG');
        $this->createAdditionalDocument($user, 'ADOC-FIN', '001HFIN');

        $response = $this->actingAs($user)->getJson(route('document-report.additional-documents-data', [
            'draw' => 1,
            'start' => 0,
            'length' => 50,
        ]));

        $response->assertOk();
        $response->assertJsonPath('recordsTotal', 2);
    }

    public function test_invoice_detail_page_is_read_only_and_shows_linked_documents_and_journey(): void
    {
        $user = $this->createLogisticUser('000HLOG');
        $invoice = $this->createInvoice($user, 'INV-DETAIL', '000HLOG');
        $additionalDocument = $this->createAdditionalDocument($user, 'ADOC-LINKED', '001HFIN');
        $invoice->additionalDocuments()->attach($additionalDocument->id);

        $response = $this->actingAs($user)->get(route('document-report.invoice-detail', $invoice));

        $response->assertOk();
        $response->assertSee('INV-DETAIL');
        $response->assertSee('ADOC-LINKED');
        $response->assertSee('Distribution Journey');
        $response->assertDontSee('Edit');
        $response->assertDontSee('Delete');
        $response->assertDontSee('Send to SAP');
    }

    public function test_additional_document_detail_shows_linked_invoices_and_journey(): void
    {
        $user = $this->createLogisticUser('000HLOG');
        $invoice = $this->createInvoice($user, 'INV-LINK', '000HLOG');
        $additionalDocument = $this->createAdditionalDocument($user, 'ADOC-DETAIL', '001HFIN');
        $invoice->additionalDocuments()->attach($additionalDocument->id);

        $response = $this->actingAs($user)->get(route('document-report.additional-document-detail', $additionalDocument));

        $response->assertOk();
        $response->assertSee('ADOC-DETAIL');
        $response->assertSee('INV-LINK');
        $response->assertSee('Distribution Journey');
        $response->assertDontSee('Edit Document');
    }

    public function test_invoice_export_returns_excel_file(): void
    {
        $user = $this->createLogisticUser('000HLOG');
        $this->createInvoice($user, 'INV-EXPORT', '000HLOG');

        $response = $this->actingAs($user)->get(route('document-report.invoices-export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_additional_documents_export_returns_excel_file(): void
    {
        $user = $this->createLogisticUser('000HLOG');
        $this->createAdditionalDocument($user, 'ADOC-EXPORT', '000HLOG');

        $response = $this->actingAs($user)->get(route('document-report.additional-documents-export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
