<?php

namespace Tests\Feature;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\AdditionalDocumentTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PasteGeneralImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private AdditionalDocumentType $documentType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdditionalDocumentTypeSeeder::class);

        Permission::findOrCreate('import-general-documents');

        $department = Department::query()->create([
            'name' => 'Accounting',
            'project' => '001H',
            'location_code' => '000HACC',
            'akronim' => 'ACC',
        ]);

        $this->user = User::factory()->create([
            'is_active' => true,
            'department_id' => $department->id,
        ]);
        $this->user->givePermissionTo('import-general-documents');

        $this->documentType = AdditionalDocumentType::query()
            ->where('type_name', 'BA')
            ->firstOrFail();
    }

    public function test_valid_lines_create_records_with_expected_fields(): void
    {
        $project = Project::query()->create([
            'code' => 'PRJ01',
            'owner' => 'Owner A',
            'location' => 'Jakarta',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(
            route('additional-documents.process-paste-general-import'),
            [
                'document_type_id' => $this->documentType->id,
                'project_id' => $project->id,
                'fallback_date' => '2026-09-01',
                'lines' => [
                    "BA-001\t4-Sep-2026",
                    'BA-002',
                ],
            ]
        );

        $response->assertRedirect(route('additional-documents.import-general'))
            ->assertSessionHas('general_import_summary');

        $first = AdditionalDocument::query()
            ->where('document_number', 'BA-001')
            ->first();

        $this->assertNotNull($first);
        $this->assertSame($this->documentType->id, $first->type_id);
        $this->assertSame('2026-09-04', $first->document_date->format('Y-m-d'));
        $this->assertSame('PRJ01', $first->project);
        $this->assertSame($this->user->id, $first->created_by);
        $this->assertNull($first->vendor_code);
        $this->assertNull($first->remarks);

        $second = AdditionalDocument::query()
            ->where('document_number', 'BA-002')
            ->first();

        $this->assertNotNull($second);
        $this->assertSame('2026-09-01', $second->document_date->format('Y-m-d'));
    }

    public function test_parses_four_sep_2026_date_format(): void
    {
        $this->actingAs($this->user)->post(
            route('additional-documents.process-paste-general-import'),
            [
                'document_type_id' => $this->documentType->id,
                'lines' => [
                    "BA-DATE-001\t4-Sep-2026",
                ],
            ]
        );

        $document = AdditionalDocument::query()
            ->where('document_number', 'BA-DATE-001')
            ->first();

        $this->assertNotNull($document);
        $this->assertSame('2026-09-04', $document->document_date->format('Y-m-d'));
    }

    public function test_line_without_date_uses_fallback_date(): void
    {
        $this->actingAs($this->user)->post(
            route('additional-documents.process-paste-general-import'),
            [
                'document_type_id' => $this->documentType->id,
                'fallback_date' => '2026-08-15',
                'lines' => [
                    'BA-NO-DATE-001',
                ],
            ]
        );

        $document = AdditionalDocument::query()
            ->where('document_number', 'BA-NO-DATE-001')
            ->first();

        $this->assertNotNull($document);
        $this->assertSame('2026-08-15', $document->document_date->format('Y-m-d'));
    }

    public function test_parses_excel_serial_numeric_date(): void
    {
        $this->actingAs($this->user)->post(
            route('additional-documents.process-paste-general-import'),
            [
                'document_type_id' => $this->documentType->id,
                'lines' => [
                    "BA-SERIAL-001\t46269",
                ],
            ]
        );

        $document = AdditionalDocument::query()
            ->where('document_number', 'BA-SERIAL-001')
            ->first();

        $this->assertNotNull($document);
        $this->assertSame('2026-09-04', $document->document_date->format('Y-m-d'));
    }

    public function test_duplicate_document_is_skipped(): void
    {
        AdditionalDocument::query()->create([
            'type_id' => $this->documentType->id,
            'document_number' => 'BA-DUP-001',
            'document_date' => '2026-09-01',
            'created_by' => $this->user->id,
            'status' => 'open',
            'cur_loc' => '000HACC',
        ]);

        $response = $this->actingAs($this->user)->post(
            route('additional-documents.process-paste-general-import'),
            [
                'document_type_id' => $this->documentType->id,
                'lines' => [
                    "BA-DUP-001\t4-Sep-2026",
                    "BA-NEW-001\t4-Sep-2026",
                ],
            ]
        );

        $response->assertRedirect(route('additional-documents.import-general'))
            ->assertSessionHas('general_import_summary');

        $summary = $response->getSession()->get('general_import_summary');

        $this->assertSame(1, $summary['success_count']);
        $this->assertSame(1, $summary['skipped_count']);
        $this->assertSame(1, AdditionalDocument::query()->where('document_number', 'BA-DUP-001')->count());
        $this->assertSame(1, AdditionalDocument::query()->where('document_number', 'BA-NEW-001')->count());
    }

    public function test_unparseable_date_produces_row_error_and_does_not_create_record(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('additional-documents.process-paste-general-import'),
            [
                'document_type_id' => $this->documentType->id,
                'lines' => [
                    "BA-BAD-DATE\tnot-a-date",
                ],
            ]
        );

        $response->assertRedirect(route('additional-documents.import-general'))
            ->assertSessionHas('general_import_summary');

        $summary = $response->getSession()->get('general_import_summary');

        $this->assertSame(0, $summary['success_count']);
        $this->assertSame(1, $summary['error_count']);
        $this->assertStringContainsString('Format tanggal tidak dikenali: not-a-date', $summary['errors'][0]);
        $this->assertNull(AdditionalDocument::query()->where('document_number', 'BA-BAD-DATE')->first());
    }

    public function test_empty_lines_array_fails_validation(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('additional-documents.process-paste-general-import'),
            [
                'document_type_id' => $this->documentType->id,
                'lines' => [],
            ]
        );

        $response->assertSessionHasErrors('lines');
    }

    public function test_more_than_500_lines_fails_validation(): void
    {
        $lines = array_fill(0, 501, 'DOC-001');

        $response = $this->actingAs($this->user)->post(
            route('additional-documents.process-paste-general-import'),
            [
                'document_type_id' => $this->documentType->id,
                'lines' => $lines,
            ]
        );

        $response->assertSessionHasErrors('lines');
    }

    public function test_user_without_permission_gets_forbidden(): void
    {
        $userWithoutPermission = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($userWithoutPermission)->post(
            route('additional-documents.process-paste-general-import'),
            [
                'document_type_id' => $this->documentType->id,
                'lines' => [
                    'BA-001',
                ],
            ]
        );

        $response->assertForbidden();
    }

    public function test_extra_tab_separated_columns_are_ignored(): void
    {
        $this->actingAs($this->user)->post(
            route('additional-documents.process-paste-general-import'),
            [
                'document_type_id' => $this->documentType->id,
                'lines' => [
                    "BA-EXTRA-001\t4-Sep-2026\tignored\talso-ignored",
                ],
            ]
        );

        $document = AdditionalDocument::query()
            ->where('document_number', 'BA-EXTRA-001')
            ->first();

        $this->assertNotNull($document);
        $this->assertSame('2026-09-04', $document->document_date->format('Y-m-d'));
    }

    public function test_import_general_page_renders_both_tabs(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('additional-documents.import-general')
        );

        $response->assertOk();
        $response->assertSee('Upload Excel', false);
        $response->assertSee('Paste Manual', false);
        // upload form + file input still present
        $response->assertSee('process-general-import', false);
        $response->assertSee('name="file"', false);
    }
}
