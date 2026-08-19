<?php

namespace Tests\Feature;

use App\Jobs\ProcessItoBatchImportJob;
use App\Models\ItoBatchImport;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class ItoBatchImportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    public function test_guest_cannot_access_ito_batch_import(): void
    {
        $this->get(route('ito-batch-import.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_ito_batch_import(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('logistic');

        $this->actingAs($user)->get(route('ito-batch-import.index'))
            ->assertForbidden();
    }

    public function test_accounting_user_can_upload_batch_pdf(): void
    {
        Queue::fake([ProcessItoBatchImportJob::class]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('accounting');

        $pdf = $this->makeUploadedPdf();

        $response = $this->actingAs($user)->post(route('ito-batch-import.store'), [
            'pdf' => $pdf,
        ]);

        $batch = ItoBatchImport::query()->first();
        $this->assertNotNull($batch);
        $response->assertRedirect(route('ito-batch-import.show', $batch));
        $response->assertSessionHas('success');
        Storage::disk('public')->assertExists($batch->stored_path);

        Queue::assertPushed(ProcessItoBatchImportJob::class, function (ProcessItoBatchImportJob $job) use ($batch) {
            return $job->itoBatchImportId === $batch->id;
        });
    }

    private function makeUploadedPdf(): UploadedFile
    {
        $pdf = new Fpdi;
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'INVENTORY TRANSFER OUT No. 261004859');

        $path = sys_get_temp_dir().'/ito-batch-upload-'.uniqid().'.pdf';
        $pdf->Output('F', $path);

        return new UploadedFile($path, 'batch.pdf', 'application/pdf', null, true);
    }
}
