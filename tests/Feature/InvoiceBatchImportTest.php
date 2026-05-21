<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceAttachment;
use App\Models\InvoiceType;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceImportAttachmentService;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceBatchImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: int, 1: Supplier}
     */
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
            'sap_code' => 'V-BAT',
            'name' => 'Batch Vendor',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        return [$typeId, $supplier];
    }

    private function seedImportCache(User $user, string $uuid, string $invoiceNumber): void
    {
        $path = 'temp/invoice-imports/'.$uuid.'.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 fake');
        Cache::put(InvoiceImportAttachmentService::cacheKey($uuid), [
            'user_id' => $user->id,
            'status' => 'completed',
            'path' => $path,
            'mime' => 'application/pdf',
            'original_name' => $invoiceNumber.'.pdf',
            'draft' => [
                'invoice_number' => $invoiceNumber,
                'amount' => '100.00',
                'currency' => 'IDR',
                'line_items' => [],
            ],
            'extracted_at' => now()->toIso8601String(),
            'extraction_confidence' => 0.9,
        ], 3600);
    }

    /**
     * @return array<string, mixed>
     */
    private function invoicePayload(int $typeId, int $supplierId, string $invoiceNumber, string $importUuid): array
    {
        $date = now()->toDateString();

        return [
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplierId,
            'currency' => 'IDR',
            'amount' => '100.00',
            'type_id' => $typeId,
            'cur_loc' => '001HFIN',
            'import_uuid' => $importUuid,
        ];
    }

    public function test_show_redirects_when_import_key_missing(): void
    {
        Config::set('services.openrouter.enabled', true);
        Config::set('services.openrouter.key', null);

        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->get(route('invoices.import-batch'));

        $response->assertRedirect(route('invoices.index'));
    }

    public function test_show_renders_batch_default_supplier_dropdown(): void
    {
        Config::set('services.openrouter.enabled', true);
        Config::set('services.openrouter.key', 'test-key');

        $user = User::factory()->create(['is_active' => true]);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
        $user->assignRole('admin');

        Supplier::query()->create([
            'sap_code' => 'V-DEF',
            'name' => 'Bulk Default Supplier',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('invoices.import-batch'));

        $response->assertOk();
        $response->assertSee('id="batch_default_supplier_id"', false);
        $response->assertSee('Bulk Default Supplier', false);
    }

    public function test_store_returns_503_when_import_key_missing(): void
    {
        Config::set('services.openrouter.enabled', true);
        Config::set('services.openrouter.key', null);

        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->postJson(route('invoices.import-batch.store'), [
            'invoices' => [],
        ]);

        $response->assertStatus(503);
    }

    public function test_batch_store_creates_multiple_invoices(): void
    {
        Config::set('services.openrouter.enabled', true);
        Config::set('services.openrouter.key', 'test-key');
        Config::set('services.openrouter.batch_import_max', 50);

        $user = User::factory()->create(['is_active' => true]);
        [$typeId, $supplier] = $this->seedBasics($user);

        $uuid1 = (string) Str::uuid();
        $uuid2 = (string) Str::uuid();
        $this->seedImportCache($user, $uuid1, 'BATCH-INV-1');
        $this->seedImportCache($user, $uuid2, 'BATCH-INV-2');

        $response = $this->actingAs($user)->postJson(route('invoices.import-batch.store'), [
            'invoices' => [
                $this->invoicePayload($typeId, $supplier->id, 'BATCH-INV-1', $uuid1),
                $this->invoicePayload($typeId, $supplier->id, 'BATCH-INV-2', $uuid2),
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('created_count', 2);

        $this->assertDatabaseHas('invoices', ['invoice_number' => 'BATCH-INV-1', 'supplier_id' => $supplier->id]);
        $this->assertDatabaseHas('invoices', ['invoice_number' => 'BATCH-INV-2', 'supplier_id' => $supplier->id]);
    }

    public function test_batch_store_attaches_uploaded_pdf_as_invoice_copy(): void
    {
        Config::set('services.openrouter.enabled', true);
        Config::set('services.openrouter.key', 'test-key');

        $user = User::factory()->create(['is_active' => true]);
        [$typeId, $supplier] = $this->seedBasics($user);

        $uuid = (string) Str::uuid();
        $tempPath = 'temp/invoice-imports/'.$uuid.'.pdf';
        Storage::disk('local')->put($tempPath, '%PDF-1.4 batch attachment test');
        $this->seedImportCache($user, $uuid, 'BATCH-ATT-1');

        $response = $this->actingAs($user)->postJson(route('invoices.import-batch.store'), [
            'invoices' => [
                $this->invoicePayload($typeId, $supplier->id, 'BATCH-ATT-1', $uuid),
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.import_attachment_saved', true);

        $invoice = Invoice::query()->where('invoice_number', 'BATCH-ATT-1')->firstOrFail();
        $attachment = InvoiceAttachment::query()->where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($attachment);
        $this->assertSame('Invoice Copy', $attachment->category);
        $this->assertSame('BATCH-ATT-1.pdf', $attachment->file_name);
        $this->assertSame('application/pdf', $attachment->mime_type);
        $this->assertTrue(Storage::disk('local')->exists($attachment->file_path));
        $this->assertFalse(Storage::disk('local')->exists($tempPath));
    }

    public function test_batch_store_attaches_file_when_extraction_failed_but_upload_remains(): void
    {
        Config::set('services.openrouter.enabled', true);
        Config::set('services.openrouter.key', 'test-key');

        $user = User::factory()->create(['is_active' => true]);
        [$typeId, $supplier] = $this->seedBasics($user);

        $uuid = (string) Str::uuid();
        $tempPath = 'temp/invoice-imports/'.$uuid.'.jpg';
        Storage::disk('local')->put($tempPath, 'fake-image-bytes');
        Cache::put(InvoiceImportAttachmentService::cacheKey($uuid), [
            'user_id' => $user->id,
            'status' => 'failed',
            'path' => $tempPath,
            'mime' => 'image/jpeg',
            'original_name' => 'scan.jpg',
            'error' => 'OCR failed',
        ], 3600);

        $response = $this->actingAs($user)->postJson(route('invoices.import-batch.store'), [
            'invoices' => [
                $this->invoicePayload($typeId, $supplier->id, 'BATCH-FAIL-ATT', $uuid),
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('results.0.import_attachment_saved', true);

        $invoice = Invoice::query()->where('invoice_number', 'BATCH-FAIL-ATT')->firstOrFail();
        $this->assertDatabaseHas('invoice_attachments', [
            'invoice_id' => $invoice->id,
            'file_name' => 'scan.jpg',
            'category' => 'Invoice Copy',
        ]);
    }

    public function test_batch_store_second_duplicate_invoice_number_in_batch_fails(): void
    {
        Config::set('services.openrouter.enabled', true);
        Config::set('services.openrouter.key', 'test-key');

        $user = User::factory()->create(['is_active' => true]);
        [$typeId, $supplier] = $this->seedBasics($user);

        $uuid1 = (string) Str::uuid();
        $uuid2 = (string) Str::uuid();
        $this->seedImportCache($user, $uuid1, 'X-1');
        $this->seedImportCache($user, $uuid2, 'X-2');

        $first = $this->invoicePayload($typeId, $supplier->id, 'SAME-NUM', $uuid1);
        $second = $this->invoicePayload($typeId, $supplier->id, 'SAME-NUM', $uuid2);

        $response = $this->actingAs($user)->postJson(route('invoices.import-batch.store'), [
            'invoices' => [$first, $second],
        ]);

        $response->assertOk();
        $response->assertJsonPath('created_count', 1);
        $results = $response->json('results');
        $this->assertCount(2, $results);
        $this->assertTrue(collect($results)->contains(fn (array $r): bool => $r['status'] === 'validation_failed'));
        $this->assertTrue(collect($results)->contains(fn (array $r): bool => $r['status'] === 'created'));
    }

    public function test_batch_store_rejects_more_than_max_invoices(): void
    {
        Config::set('services.openrouter.enabled', true);
        Config::set('services.openrouter.key', 'test-key');
        Config::set('services.openrouter.batch_import_max', 2);

        $user = User::factory()->create(['is_active' => true]);
        [$typeId, $supplier] = $this->seedBasics($user);
        $u1 = (string) Str::uuid();
        $u2 = (string) Str::uuid();
        $u3 = (string) Str::uuid();
        $this->seedImportCache($user, $u1, 'M1');
        $this->seedImportCache($user, $u2, 'M2');
        $this->seedImportCache($user, $u3, 'M3');

        $response = $this->actingAs($user)->postJson(route('invoices.import-batch.store'), [
            'invoices' => [
                $this->invoicePayload($typeId, $supplier->id, 'M1', $u1),
                $this->invoicePayload($typeId, $supplier->id, 'M2', $u2),
                $this->invoicePayload($typeId, $supplier->id, 'M3', $u3),
            ],
        ]);

        $response->assertStatus(422);
    }
}
