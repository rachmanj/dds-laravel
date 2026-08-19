<?php

namespace Tests\Feature;

use App\Jobs\ProcessItoBatchImportJob;
use App\Jobs\VerifyDocumentSignatureJob;
use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\ItoBatchImport;
use App\Models\User;
use Database\Seeders\AdditionalDocumentTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class ProcessItoBatchImportJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AdditionalDocumentTypeSeeder::class);
    }

    public function test_job_attaches_matched_page_and_dispatches_signature_verification(): void
    {
        Queue::fake([VerifyDocumentSignatureJob::class]);

        $user = User::factory()->create(['is_active' => true]);
        $type = AdditionalDocumentType::query()->where('type_name', 'ITO')->firstOrFail();
        $date = now()->toDateString();

        $document = AdditionalDocument::query()->create([
            'type_id' => $type->id,
            'document_number' => '261004850',
            'document_date' => $date,
            'receive_date' => $date,
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => 'DEFAULT',
            'project' => '001H',
        ]);

        $storedPath = $this->storeBatchPdf(1, '261004850');
        $batch = ItoBatchImport::query()->create([
            'filename' => 'batch.pdf',
            'stored_path' => $storedPath,
            'total_pages' => 0,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        $job = new ProcessItoBatchImportJob($batch->id);
        $job->handle(app(\App\Services\ItoBatchImportService::class));

        $batch->refresh();
        $document->refresh();

        $this->assertSame('processed', $batch->status);
        $this->assertSame(1, $batch->total_pages);
        $this->assertNotNull($document->attachment);
        $this->assertNotNull($document->batch_no);
        $this->assertDatabaseHas('ito_batch_items', [
            'batch_id' => $batch->id,
            'page_number' => 1,
            'status' => 'matched',
            'matched_document_id' => $document->id,
            'extracted_ito_no' => '261004850',
        ]);

        Queue::assertPushed(VerifyDocumentSignatureJob::class, function (VerifyDocumentSignatureJob $job) use ($document) {
            return $job->additionalDocumentId === $document->id;
        });
    }

    public function test_job_marks_not_found_when_no_matching_document(): void
    {
        Queue::fake([VerifyDocumentSignatureJob::class]);

        $user = User::factory()->create(['is_active' => true]);
        $storedPath = $this->storeBatchPdf(1, '261004851');
        $batch = ItoBatchImport::query()->create([
            'filename' => 'batch.pdf',
            'stored_path' => $storedPath,
            'total_pages' => 0,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        $job = new ProcessItoBatchImportJob($batch->id);
        $job->handle(app(\App\Services\ItoBatchImportService::class));

        $batch->refresh();
        $this->assertSame('partial', $batch->status);
        $this->assertDatabaseHas('ito_batch_items', [
            'batch_id' => $batch->id,
            'page_number' => 1,
            'status' => 'not_found',
            'extracted_ito_no' => '261004851',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_job_marks_ambiguous_when_multiple_documents_match(): void
    {
        Queue::fake([VerifyDocumentSignatureJob::class]);

        $user = User::factory()->create(['is_active' => true]);
        $type = AdditionalDocumentType::query()->where('type_name', 'ITO')->firstOrFail();
        $date = now()->toDateString();

        foreach (['261-004-852', '261004852'] as $number) {
            AdditionalDocument::query()->create([
                'type_id' => $type->id,
                'document_number' => $number,
                'document_date' => $date,
                'receive_date' => $date,
                'created_by' => $user->id,
                'status' => 'open',
                'cur_loc' => 'DEFAULT',
            ]);
        }

        $storedPath = $this->storeBatchPdf(1, '261004852');
        $batch = ItoBatchImport::query()->create([
            'filename' => 'batch.pdf',
            'stored_path' => $storedPath,
            'total_pages' => 0,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        $job = new ProcessItoBatchImportJob($batch->id);
        $job->handle(app(\App\Services\ItoBatchImportService::class));

        $this->assertDatabaseHas('ito_batch_items', [
            'batch_id' => $batch->id,
            'page_number' => 1,
            'status' => 'ambiguous',
        ]);

        Queue::assertNothingPushed();
    }

    private function storeBatchPdf(int $pages, string $itoNumber): string
    {
        $pdf = new Fpdi;
        for ($i = 1; $i <= $pages; $i++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Cell(0, 10, 'INVENTORY TRANSFER OUT No. '.$itoNumber);
        }

        $relativePath = 'ito-batch-imports/test-'.uniqid().'.pdf';
        $absolute = storage_path('app/public/'.$relativePath);
        @mkdir(dirname($absolute), 0777, true);
        $pdf->Output('F', $absolute);

        return $relativePath;
    }
}
