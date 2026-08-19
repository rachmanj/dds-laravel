<?php

namespace Tests\Feature;

use App\Jobs\VerifyDocumentSignatureJob;
use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\Project;
use App\Models\SignatureSpecimen;
use App\Models\SignatureSpecimenImage;
use App\Models\User;
use App\Services\SignatureMatchingService;
use Database\Seeders\AdditionalDocumentTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VerifyDocumentSignatureJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdditionalDocumentTypeSeeder::class);
    }

    private function createDoDocument(User $user, ?string $attachmentPath = null, ?int $projectId = null): AdditionalDocument
    {
        $type = AdditionalDocumentType::query()->where('type_name', 'Delivery Order (DO)')->firstOrFail();
        $date = now()->toDateString();

        return AdditionalDocument::query()->create([
            'type_id' => $type->id,
            'document_number' => 'DO-'.uniqid(),
            'document_date' => $date,
            'receive_date' => $date,
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => 'LOC1',
            'project' => '001H',
            'attachment' => $attachmentPath,
            'signature_project_id' => $projectId,
            'signature_status' => $projectId ? 'pending' : null,
        ]);
    }

    public function test_job_skips_when_no_attachment(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $project = Project::query()->create([
            'code' => '001H',
            'owner' => 'HO Jakarta',
            'location' => 'Jakarta',
            'is_active' => true,
        ]);

        $document = $this->createDoDocument($user, null, $project->id);

        $job = new VerifyDocumentSignatureJob($document->id);
        $job->handle(app(SignatureMatchingService::class));

        $document->refresh();
        $this->assertSame('skipped', $document->signature_status);
    }

    public function test_job_sets_no_match_when_no_candidates(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $project = Project::query()->create([
            'code' => '001H',
            'owner' => 'HO Jakarta',
            'location' => 'Jakarta',
            'is_active' => true,
        ]);

        $attachment = 'attachments/test-do.jpg';
        $absolute = storage_path('app/public/'.$attachment);
        @mkdir(dirname($absolute), 0777, true);
        file_put_contents($absolute, 'scan');

        $document = $this->createDoDocument($user, $attachment, $project->id);

        $job = new VerifyDocumentSignatureJob($document->id);
        $job->handle(app(SignatureMatchingService::class));

        $document->refresh();
        $this->assertSame('no_match', $document->signature_status);
        $this->assertDatabaseCount('signature_match_results', 0);

        @unlink($absolute);
    }

    public function test_job_stores_match_results_on_happy_path(): void
    {
        Config::set('services.openrouter.key', 'test-key');
        Config::set('services.openrouter.model', 'openai/gpt-4o');
        Config::set('services.openrouter.signature_match_threshold', 0.75);
        Config::set('services.openrouter.signature_uncertain_threshold', 0.45);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'score' => 0.9,
                                'verdict' => 'matched',
                                'reasoning' => 'Signatures align.',
                                'document_signature_crop' => null,
                                'specimen_signature_crop' => null,
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $project = Project::query()->create([
            'code' => '001H',
            'owner' => 'HO Jakarta',
            'location' => 'Jakarta',
            'is_active' => true,
        ]);

        $attachment = 'attachments/test-do-happy.jpg';
        $specimenPath = 'signature-specimens/spec.jpg';
        $attachmentAbsolute = storage_path('app/public/'.$attachment);
        $specimenAbsolute = storage_path('app/public/'.$specimenPath);
        @mkdir(dirname($attachmentAbsolute), 0777, true);
        @mkdir(dirname($specimenAbsolute), 0777, true);
        file_put_contents($attachmentAbsolute, 'scan');
        file_put_contents($specimenAbsolute, 'specimen');

        $specimen = SignatureSpecimen::query()->create([
            'name' => 'Receiver One',
            'is_active' => true,
        ]);
        $specimen->projects()->attach($project->id);
        SignatureSpecimenImage::query()->create([
            'specimen_id' => $specimen->id,
            'path' => $specimenPath,
        ]);

        $document = $this->createDoDocument($user, $attachment, $project->id);

        $job = new VerifyDocumentSignatureJob($document->id);
        $job->handle(app(SignatureMatchingService::class));

        $document->refresh();
        $this->assertSame('matched', $document->signature_status);
        $this->assertDatabaseHas('signature_match_results', [
            'additional_document_id' => $document->id,
            'specimen_id' => $specimen->id,
            'verdict' => 'matched',
        ]);

        @unlink($attachmentAbsolute);
        @unlink($specimenAbsolute);
    }
}
