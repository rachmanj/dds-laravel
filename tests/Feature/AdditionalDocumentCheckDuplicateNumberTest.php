<?php

namespace Tests\Feature;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\User;
use Database\Seeders\AdditionalDocumentTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdditionalDocumentCheckDuplicateNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdditionalDocumentTypeSeeder::class);
    }

    public function test_check_duplicate_number_returns_existing_document(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');
        $doType = AdditionalDocumentType::query()->where('type_name', 'Delivery Order (DO)')->firstOrFail();

        $existing = AdditionalDocument::query()->create([
            'type_id' => $doType->id,
            'document_number' => 'DO-DUP-001',
            'document_date' => '2026-09-01',
            'receive_date' => '2026-09-01',
            'vendor_code' => 'VCASJIDR01',
            'po_no' => 'PO-123',
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => '000HACC',
        ]);

        $response = $this->actingAs($user)->postJson(
            route('additional-documents.check-duplicate-number'),
            [
                'type_id' => $doType->id,
                'document_number' => 'DO-DUP-001',
                'vendor_code' => 'VCASJIDR01',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('document.id', $existing->id)
            ->assertJsonPath('document.document_number', 'DO-DUP-001')
            ->assertJsonPath('document.po_no', 'PO-123')
            ->assertJsonPath('document.vendor_code', 'VCASJIDR01')
            ->assertJsonPath('document.document_date', '01/09/2026');
    }

    public function test_check_duplicate_number_excludes_current_record_on_edit(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');
        $doType = AdditionalDocumentType::query()->where('type_name', 'Delivery Order (DO)')->firstOrFail();

        $document = AdditionalDocument::query()->create([
            'type_id' => $doType->id,
            'document_number' => 'DO-SELF-001',
            'document_date' => '2026-09-01',
            'receive_date' => '2026-09-01',
            'vendor_code' => 'VCASJIDR01',
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => '000HACC',
        ]);

        $response = $this->actingAs($user)->postJson(
            route('additional-documents.check-duplicate-number'),
            [
                'type_id' => $doType->id,
                'document_number' => 'DO-SELF-001',
                'vendor_code' => 'VCASJIDR01',
                'exclude_id' => $document->id,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('exists', false)
            ->assertJsonPath('document', null);
    }

    public function test_check_duplicate_number_ignores_vendor_code_when_empty(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');
        $doType = AdditionalDocumentType::query()->where('type_name', 'Delivery Order (DO)')->firstOrFail();

        AdditionalDocument::query()->create([
            'type_id' => $doType->id,
            'document_number' => 'DO-NO-VENDOR',
            'document_date' => '2026-09-01',
            'receive_date' => '2026-09-01',
            'vendor_code' => 'VCASJIDR01',
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => '000HACC',
        ]);

        $response = $this->actingAs($user)->postJson(
            route('additional-documents.check-duplicate-number'),
            [
                'type_id' => $doType->id,
                'document_number' => 'DO-NO-VENDOR',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('exists', true);
    }

    public function test_check_duplicate_number_requires_authentication(): void
    {
        $response = $this->postJson(route('additional-documents.check-duplicate-number'), [
            'type_id' => 1,
            'document_number' => 'DO-001',
        ]);

        $response->assertUnauthorized();
    }
}
