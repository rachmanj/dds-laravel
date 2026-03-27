<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvoiceImportExtractTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_extract_returns_503_when_api_key_missing(): void
    {
        Config::set('services.openrouter.key', null);
        Config::set('services.openrouter.enabled', true);

        $user = User::factory()->create(['is_active' => true]);
        $file = UploadedFile::fake()->image('doc.jpg', 80, 80);

        $response = $this->actingAs($user)->postJson(route('invoices.import-extract'), [
            'file' => $file,
        ]);

        $response->assertStatus(503);
    }

    public function test_import_extract_completes_and_returns_draft(): void
    {
        Config::set('services.openrouter.key', 'test-key');
        Config::set('services.openrouter.enabled', true);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'invoice_number' => 'INV-TEST-001',
                                'supplier_name' => 'Acme Trading Co',
                                'currency' => 'IDR',
                                'amount' => 1500000.50,
                                'invoice_date' => '2025-06-01',
                                'confidence' => 0.88,
                                'warnings' => [],
                                'line_items' => [],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'project' => null,
            'is_active' => true,
        ]);
        Supplier::create([
            'name' => 'Acme Trading Co',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $file = UploadedFile::fake()->image('doc.jpg', 80, 80);

        $extract = $this->actingAs($user)->postJson(route('invoices.import-extract'), [
            'file' => $file,
        ]);

        $extract->assertOk();
        $extract->assertJsonPath('success', true);
        $extract->assertJsonPath('status', 'completed');
        $uuid = $extract->json('uuid');
        $this->assertNotEmpty($uuid);

        $draft = $this->actingAs($user)->getJson(route('invoices.import-draft', ['uuid' => $uuid]));
        $draft->assertOk();
        $draft->assertJsonPath('draft.invoice_number', 'INV-TEST-001');
        $draft->assertJsonPath('draft.currency', 'IDR');
    }
}
