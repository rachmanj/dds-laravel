<?php

namespace Tests\Unit;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Models\User;
use App\Services\ItoBatchImportService;
use Database\Seeders\AdditionalDocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class ItoBatchImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ItoBatchImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdditionalDocumentTypeSeeder::class);
        $this->service = new ItoBatchImportService;
    }

    public function test_normalize_number_strips_non_alphanumeric_and_uppercases(): void
    {
        $this->assertSame('ITO261004859', $this->service->normalizeNumber('ito-261004859'));
        $this->assertSame('261004859', $this->service->normalizeNumber(' 261 004 859 '));
        $this->assertSame('', $this->service->normalizeNumber(null));
    }

    public function test_match_document_returns_not_found_when_no_match(): void
    {
        $result = $this->service->matchDocument('261004859');

        $this->assertSame('not_found', $result['status']);
        $this->assertNull($result['document']);
    }

    public function test_match_document_returns_matched_for_unique_ito(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $type = AdditionalDocumentType::query()->where('type_name', 'ITO')->firstOrFail();

        $document = AdditionalDocument::query()->create([
            'type_id' => $type->id,
            'document_number' => '261004859',
            'document_date' => now()->toDateString(),
            'receive_date' => now()->toDateString(),
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => 'DEFAULT',
        ]);

        $result = $this->service->matchDocument('261004859');

        $this->assertSame('matched', $result['status']);
        $this->assertSame($document->id, $result['document']?->id);
    }

    public function test_match_document_returns_ambiguous_for_multiple_matches(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $type = AdditionalDocumentType::query()->where('type_name', 'ITO')->firstOrFail();
        $date = now()->toDateString();

        AdditionalDocument::query()->create([
            'type_id' => $type->id,
            'document_number' => '261-004-859',
            'document_date' => $date,
            'receive_date' => $date,
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => 'DEFAULT',
        ]);

        AdditionalDocument::query()->create([
            'type_id' => $type->id,
            'document_number' => '261004859',
            'document_date' => $date,
            'receive_date' => $date,
            'created_by' => $user->id,
            'status' => 'open',
            'cur_loc' => 'DEFAULT',
        ]);

        $result = $this->service->matchDocument('261004859');

        $this->assertSame('ambiguous', $result['status']);
        $this->assertCount(2, $result['documents']);
    }

    public function test_split_page_extracts_single_page_pdf(): void
    {
        $sourcePath = $this->createTestPdf(2);
        $splitPath = $this->service->splitPage($sourcePath, 2);

        $this->assertFileExists(storage_path('app/public/'.$splitPath));
        $this->assertSame(1, $this->service->countPages(storage_path('app/public/'.$splitPath)));

        @unlink($sourcePath);
        @unlink(storage_path('app/public/'.$splitPath));
    }

    public function test_extract_ito_number_uses_openrouter_when_text_layer_insufficient(): void
    {
        Config::set('services.openrouter.key', 'test-key');
        Config::set('services.openrouter.model', 'openai/gpt-4o');
        Config::set('services.openrouter.batch_ocr_model', 'openai/gpt-4o');

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'ito_no' => '261004859',
                                'confidence' => 0.92,
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $pdfPath = $this->createTestPdf(1, 'short');
        $relativePath = 'ito-batch-pages/test-ocr.pdf';
        $absolute = storage_path('app/public/'.$relativePath);
        @mkdir(dirname($absolute), 0777, true);
        copy($pdfPath, $absolute);

        $result = $this->service->extractItoNumber($relativePath);

        $this->assertSame('261004859', $result['ito_no']);
        $this->assertSame(0.92, $result['confidence']);
        Http::assertSentCount(1);

        @unlink($pdfPath);
        @unlink($absolute);
    }

    private function createTestPdf(int $pages, string $labelPrefix = 'INVENTORY TRANSFER OUT No.'): string
    {
        $pdf = new Fpdi;
        for ($i = 1; $i <= $pages; $i++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Cell(0, 10, $labelPrefix.' 26100485'.($i - 1));
        }

        $path = storage_path('app/test-batch-'.uniqid().'.pdf');
        $pdf->Output('F', $path);

        return $path;
    }
}
