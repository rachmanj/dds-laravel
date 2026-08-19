<?php

namespace Tests\Feature;

use App\Jobs\CreateSapApInvoiceJob;
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
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InvoiceSignatureSubmissionGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
        $this->seed(AdditionalDocumentTypeSeeder::class);
    }

    private function createSapReadyInvoice(User $user): Invoice
    {
        $typeId = InvoiceType::query()->firstOrFail()->id;
        $supplier = Supplier::query()->create([
            'sap_code' => 'V-SIG',
            'name' => 'Signature Vendor',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        $date = now()->toDateString();

        return Invoice::query()->create([
            'invoice_number' => 'INV-SIG-'.uniqid(),
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'po_no' => '12345',
            'currency' => 'IDR',
            'amount' => 1_000_000,
            'type_id' => $typeId,
            'created_by' => $user->id,
            'status' => 'sap',
            'cur_loc' => 'LOC1',
        ]);
    }

    public function test_submit_to_sap_blocked_when_linked_do_has_pending_signature(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user);

        $doType = AdditionalDocumentType::query()->where('type_name', 'Delivery Order (DO)')->firstOrFail();
        $date = now()->toDateString();

        $document = AdditionalDocument::query()->create([
            'type_id' => $doType->id,
            'document_number' => 'DO-BLOCK-001',
            'document_date' => $date,
            'receive_date' => $date,
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => 'LOC1',
            'signature_status' => 'pending',
            'attachment' => 'attachments/do.pdf',
        ]);

        $invoice->additionalDocuments()->attach($document->id);

        $response = $this->actingAs($user)->post(route('invoices.submit-to-sap', $invoice), [
            'grpo_references' => [
                [
                    'grpo_no' => '5001',
                    'doc_entry' => 99,
                    'base_line' => 0,
                    'item_code' => 'ITEM-A',
                    'quantity' => 10,
                    'unit_price' => 100000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('sap_sync');
        Queue::assertNotPushed(CreateSapApInvoiceJob::class);
    }

    public function test_submit_to_sap_allowed_when_signature_override_recorded(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createSapReadyInvoice($user);

        $doType = AdditionalDocumentType::query()->where('type_name', 'ITO')->firstOrFail();
        $date = now()->toDateString();

        $document = AdditionalDocument::query()->create([
            'type_id' => $doType->id,
            'document_number' => 'ITO-OK-001',
            'document_date' => $date,
            'receive_date' => $date,
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => 'LOC1',
            'signature_status' => 'no_match',
            'signature_override_reason' => 'Manual verification by accounting.',
            'signature_override_by' => $user->id,
            'signature_override_at' => now(),
        ]);

        $invoice->additionalDocuments()->attach($document->id);

        $response = $this->actingAs($user)->post(route('invoices.submit-to-sap', $invoice), [
            'grpo_references' => [
                [
                    'grpo_no' => '5001',
                    'doc_entry' => 99,
                    'base_line' => 0,
                    'item_code' => 'ITEM-A',
                    'quantity' => 10,
                    'unit_price' => 100000,
                ],
            ],
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('success');
        Queue::assertPushed(CreateSapApInvoiceJob::class);
    }
}
