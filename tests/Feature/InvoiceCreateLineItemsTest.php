<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceLineDetail;
use App\Models\InvoiceType;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceImportAttachmentService;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceCreateLineItemsTest extends TestCase
{
    use RefreshDatabase;

    private function seedBasics(User $user): array
    {
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
            'sap_code' => 'V-CLI',
            'name' => 'Create Lines Vendor',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        return [$typeId, $supplier];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function seedImportCache(User $user, array $draftLineItems): array
    {
        $uuid = (string) Str::uuid();
        $path = 'temp/invoice-imports/'.$uuid.'.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 fake');
        Cache::put(InvoiceImportAttachmentService::cacheKey($uuid), [
            'user_id' => $user->id,
            'status' => 'completed',
            'path' => $path,
            'mime' => 'application/pdf',
            'original_name' => 'doc.pdf',
            'draft' => [
                'line_items' => $draftLineItems,
                'invoice_number' => 'IMP-1',
                'amount' => '5000.00',
                'currency' => 'IDR',
            ],
            'extracted_at' => now()->toIso8601String(),
            'extraction_confidence' => 0.9,
        ], 3600);

        return [$uuid, $path];
    }

    private function validInvoicePayload(User $user, int $typeId, int $supplierId, string $invoiceNumber, string $importUuid = ''): array
    {
        $date = now()->toDateString();

        return [
            '_token' => csrf_token(),
            'invoice_number' => $invoiceNumber,
            'faktur_no' => null,
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => (string) $supplierId,
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
            'import_uuid' => $importUuid,
            'additional_document_ids' => [],
        ];
    }

    public function test_create_with_import_and_import_line_items_persists_user_rows(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        [$typeId, $supplier] = $this->seedBasics($user);
        [$uuid] = $this->seedImportCache($user, [
            ['description' => 'Cached only', 'quantity' => '1', 'unit_price' => '1', 'amount' => '1'],
        ]);

        $payload = $this->validInvoicePayload($user, $typeId, $supplier->id, 'INV-CREATE-LINES-1', $uuid);
        $payload['import_line_items'] = [
            [
                'description' => 'User row',
                'quantity' => '2',
                'unit_price' => '50',
                'amount' => '100',
            ],
        ];

        $response = $this->actingAs($user)->post(route('invoices.store'), $payload);
        $response->assertRedirect(route('invoices.index'));

        $invoice = Invoice::query()->where('invoice_number', 'INV-CREATE-LINES-1')->firstOrFail();
        $lines = InvoiceLineDetail::query()->where('invoice_id', $invoice->id)->orderBy('line_no')->get();
        $this->assertCount(1, $lines);
        $this->assertSame('User row', $lines->first()->description);
        $this->assertSame('100.00', $lines->first()->amount);
        $this->assertSame('user', $lines->first()->source);
    }

    public function test_create_with_import_without_import_line_items_uses_draft_lines(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        [$typeId, $supplier] = $this->seedBasics($user);
        [$uuid] = $this->seedImportCache($user, [
            ['description' => 'From draft', 'quantity' => '3', 'unit_price' => '10', 'amount' => '30'],
        ]);

        $payload = $this->validInvoicePayload($user, $typeId, $supplier->id, 'INV-CREATE-LINES-2', $uuid);
        $payload['amount'] = '30.00';

        $response = $this->actingAs($user)->post(route('invoices.store'), $payload);
        $response->assertRedirect(route('invoices.index'));

        $invoice = Invoice::query()->where('invoice_number', 'INV-CREATE-LINES-2')->firstOrFail();
        $lines = InvoiceLineDetail::query()->where('invoice_id', $invoice->id)->get();
        $this->assertCount(1, $lines);
        $this->assertSame('From draft', $lines->first()->description);
        $this->assertSame('import', $lines->first()->source);
    }

    public function test_create_without_import_uuid_ignores_submitted_import_line_items(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        [$typeId, $supplier] = $this->seedBasics($user);

        $payload = $this->validInvoicePayload($user, $typeId, $supplier->id, 'INV-CREATE-LINES-3', '');
        $payload['import_line_items'] = [
            [
                'description' => 'Should not save',
                'quantity' => '1',
                'unit_price' => '1',
                'amount' => '1',
            ],
        ];

        $response = $this->actingAs($user)->post(route('invoices.store'), $payload);
        $response->assertRedirect(route('invoices.index'));

        $invoice = Invoice::query()->where('invoice_number', 'INV-CREATE-LINES-3')->firstOrFail();
        $this->assertSame(0, InvoiceLineDetail::query()->where('invoice_id', $invoice->id)->count());
    }

    public function test_create_rejects_invalid_line_amount(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        [$typeId, $supplier] = $this->seedBasics($user);
        [$uuid] = $this->seedImportCache($user, []);

        $payload = $this->validInvoicePayload($user, $typeId, $supplier->id, 'INV-CREATE-LINES-4', $uuid);
        $payload['import_line_items'] = [
            [
                'description' => 'Bad',
                'quantity' => '1',
                'unit_price' => '1',
                'amount' => 'not-a-number',
            ],
        ];

        $response = $this->actingAs($user)->post(route('invoices.store'), $payload);
        $response->assertSessionHasErrors(['import_line_items.0.amount']);
        $this->assertSame(0, Invoice::query()->where('invoice_number', 'INV-CREATE-LINES-4')->count());
    }
}
