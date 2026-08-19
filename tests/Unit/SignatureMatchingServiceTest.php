<?php

namespace Tests\Unit;

use App\Services\SignatureMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SignatureMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_verdict_uses_config_thresholds(): void
    {
        Config::set('services.openrouter.signature_match_threshold', 0.75);
        Config::set('services.openrouter.signature_uncertain_threshold', 0.45);

        $service = new SignatureMatchingService;

        $this->assertSame('matched', $service->resolveVerdict(0.8, 'matched'));
        $this->assertSame('uncertain', $service->resolveVerdict(0.5, 'uncertain'));
        $this->assertSame('no_match', $service->resolveVerdict(0.2, 'matched'));
        $this->assertSame('no_match', $service->resolveVerdict(0.8, 'no_match'));
    }

    public function test_match_candidates_returns_empty_for_no_candidates(): void
    {
        $service = new SignatureMatchingService;
        $path = storage_path('app/public/attachments/test.jpg');
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, 'fake-image');

        $results = $service->matchCandidates($path, 'image/jpeg', []);

        $this->assertSame([], $results);
        @unlink($path);
    }

    public function test_match_single_candidate_parses_openrouter_response(): void
    {
        Config::set('services.openrouter.key', 'test-key');
        Config::set('services.openrouter.signature_model', 'google/gemini-2.5-flash');
        Config::set('services.openrouter.signature_match_threshold', 0.75);
        Config::set('services.openrouter.signature_uncertain_threshold', 0.45);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'score' => 0.82,
                                'verdict' => 'matched',
                                'reasoning' => 'Strong match on stroke patterns.',
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $specPath = storage_path('app/public/signature-specimens/spec.jpg');
        $this->writeTinyJpeg($specPath);

        $service = new SignatureMatchingService;
        $documentPayload = [
            'type' => 'image',
            'content' => 'data:image/jpeg;base64,'.base64_encode('doc'),
        ];

        $result = $service->matchSingleCandidate($documentPayload, 'image/jpeg', [
            'id' => 5,
            'name' => 'John Doe',
            'image_path' => $specPath,
        ]);

        $this->assertSame(5, $result['specimen_id']);
        $this->assertSame(0.82, $result['score']);
        $this->assertSame('matched', $result['verdict']);
        $this->assertSame('Strong match on stroke patterns.', $result['reasoning']);

        @unlink($specPath);
    }

    public function test_match_candidates_crops_image_and_sends_two_image_urls(): void
    {
        Config::set('services.openrouter.key', 'test-key');
        Config::set('services.openrouter.signature_model', 'google/gemini-2.5-flash');
        Config::set('services.openrouter.signature_crop_bottom_ratio', 0.30);
        Config::set('services.openrouter.signature_match_threshold', 0.75);
        Config::set('services.openrouter.signature_uncertain_threshold', 0.45);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'score' => 0.91,
                                'verdict' => 'matched',
                                'reasoning' => 'Same writer.',
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $docPath = storage_path('app/public/attachments/doc-crop.jpg');
        $specPath = storage_path('app/public/signature-specimens/spec-crop.jpg');
        $this->writeTinyJpeg($docPath, 40, 60);
        $this->writeTinyJpeg($specPath, 20, 20);

        $service = new SignatureMatchingService;
        $results = $service->matchCandidates($docPath, 'image/jpeg', [
            [
                'id' => 7,
                'name' => 'Jane Roe',
                'image_path' => $specPath,
            ],
        ]);

        $this->assertCount(1, $results);
        $this->assertSame(7, $results[0]['specimen_id']);
        $this->assertSame(0.91, $results[0]['score']);
        $this->assertSame('matched', $results[0]['verdict']);

        Http::assertSent(function (Request $request) {
            $payload = $request->data();

            if (($payload['model'] ?? null) !== 'google/gemini-2.5-flash') {
                return false;
            }

            if (isset($payload['plugins'])) {
                return false;
            }

            if (($payload['response_format']['type'] ?? null) !== 'json_object') {
                return false;
            }

            if (count($payload['messages'] ?? []) !== 1) {
                return false;
            }

            $message = $payload['messages'][0];
            if (($message['role'] ?? null) !== 'user') {
                return false;
            }

            $content = $message['content'] ?? [];
            if (count($content) !== 3) {
                return false;
            }

            if (($content[0]['type'] ?? null) !== 'text') {
                return false;
            }

            if (! str_contains((string) $content[0]['text'], 'Received by')) {
                return false;
            }

            if (($content[1]['type'] ?? null) !== 'image_url' || ($content[2]['type'] ?? null) !== 'image_url') {
                return false;
            }

            $documentUrl = $content[1]['image_url']['url'] ?? '';
            if (! str_starts_with($documentUrl, 'data:image/jpeg;base64,')) {
                return false;
            }

            $crop = imagecreatefromstring(base64_decode(substr($documentUrl, strlen('data:image/jpeg;base64,'))));
            if ($crop === false) {
                return false;
            }

            $width = imagesx($crop);
            $height = imagesy($crop);
            imagedestroy($crop);

            return $width === 40 && $height === 18;
        });

        @unlink($docPath);
        @unlink($specPath);
    }

    public function test_match_candidates_extracts_pdf_image_then_crops(): void
    {
        Config::set('services.openrouter.key', 'test-key');
        Config::set('services.openrouter.signature_model', 'google/gemini-2.5-flash');
        Config::set('services.openrouter.signature_crop_bottom_ratio', 0.30);
        Config::set('services.openrouter.signature_match_threshold', 0.75);
        Config::set('services.openrouter.signature_uncertain_threshold', 0.45);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'score' => 0.5,
                                'verdict' => 'uncertain',
                                'reasoning' => 'Partial overlap.',
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $pdfPath = storage_path('app/public/attachments/scanned-do.pdf');
        $specPath = storage_path('app/public/signature-specimens/spec-pdf.jpg');
        $this->writeScannedPdf($pdfPath, 40, 60);
        $this->writeTinyJpeg($specPath, 20, 20);

        $service = new SignatureMatchingService;
        $results = $service->matchCandidates($pdfPath, 'application/pdf', [
            [
                'id' => 9,
                'name' => 'Receiver',
                'image_path' => $specPath,
            ],
        ]);

        $this->assertCount(1, $results);
        $this->assertSame('uncertain', $results[0]['verdict']);

        Http::assertSent(function (Request $request) {
            $payload = $request->data();
            $content = $payload['messages'][0]['content'] ?? [];

            if (isset($payload['plugins'])) {
                return false;
            }

            $types = array_column($content, 'type');
            if (in_array('file', $types, true)) {
                return false;
            }

            $documentUrl = $content[1]['image_url']['url'] ?? '';
            if (! str_starts_with($documentUrl, 'data:image/jpeg;base64,')) {
                return false;
            }

            $crop = imagecreatefromstring(base64_decode(substr($documentUrl, strlen('data:image/jpeg;base64,'))));
            if ($crop === false) {
                return false;
            }

            $height = imagesy($crop);
            imagedestroy($crop);

            return $height === 18;
        });

        @unlink($pdfPath);
        @unlink($specPath);
    }

    public function test_match_candidates_throws_when_pdf_has_no_embedded_image(): void
    {
        $pdfPath = storage_path('app/public/attachments/text-only.pdf');
        @mkdir(dirname($pdfPath), 0777, true);

        $pdf = new \FPDF;
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(40, 10, 'No image');
        $pdf->Output('F', $pdfPath);

        $specPath = storage_path('app/public/signature-specimens/spec-text.jpg');
        $this->writeTinyJpeg($specPath, 10, 10);

        $service = new SignatureMatchingService;

        try {
            $service->matchCandidates($pdfPath, 'application/pdf', [
                [
                    'id' => 1,
                    'name' => 'Nobody',
                    'image_path' => $specPath,
                ],
            ]);
            $this->fail('Expected a RuntimeException for a PDF without an embedded image.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('embedded page image', $e->getMessage());
        } finally {
            @unlink($pdfPath);
            @unlink($specPath);
        }
    }

    public function test_match_single_candidate_retries_once_then_returns_uncertain_on_invalid_json(): void
    {
        Config::set('services.openrouter.key', 'test-key');
        Config::set('services.openrouter.signature_model', 'google/gemini-2.5-flash');

        Http::fake([
            'openrouter.ai/*' => Http::sequence()
                ->push([
                    'choices' => [
                        ['message' => ['content' => 'not-json']],
                    ],
                ], 200)
                ->push([
                    'choices' => [
                        ['message' => ['content' => 'still not json']],
                    ],
                ], 200),
        ]);

        $specPath = storage_path('app/public/signature-specimens/spec-retry.jpg');
        $this->writeTinyJpeg($specPath, 10, 10);

        $service = new SignatureMatchingService;
        $result = $service->matchSingleCandidate(
            [
                'type' => 'image',
                'content' => 'data:image/jpeg;base64,'.base64_encode('doc'),
            ],
            'image/jpeg',
            [
                'id' => 3,
                'name' => 'Retry Case',
                'image_path' => $specPath,
            ]
        );

        $this->assertSame('uncertain', $result['verdict']);
        $this->assertNull($result['score']);
        $this->assertSame('Could not parse JSON from model response.', $result['reasoning']);
        Http::assertSentCount(2);

        @unlink($specPath);
    }

    public function test_match_single_candidate_succeeds_on_json_retry(): void
    {
        Config::set('services.openrouter.key', 'test-key');
        Config::set('services.openrouter.signature_model', 'google/gemini-2.5-flash');
        Config::set('services.openrouter.signature_match_threshold', 0.75);

        Http::fake([
            'openrouter.ai/*' => Http::sequence()
                ->push([
                    'choices' => [
                        ['message' => ['content' => '```json']],
                    ],
                ], 200)
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'content' => json_encode([
                                    'score' => 0.2,
                                    'verdict' => 'no_match',
                                    'reasoning' => 'Different writer.',
                                ]),
                            ],
                        ],
                    ],
                ], 200),
        ]);

        $specPath = storage_path('app/public/signature-specimens/spec-retry-ok.jpg');
        $this->writeTinyJpeg($specPath, 10, 10);

        $service = new SignatureMatchingService;
        $result = $service->matchSingleCandidate(
            [
                'type' => 'image',
                'content' => 'data:image/jpeg;base64,'.base64_encode('doc'),
            ],
            'image/jpeg',
            [
                'id' => 4,
                'name' => 'Retry Ok',
                'image_path' => $specPath,
            ]
        );

        $this->assertSame('no_match', $result['verdict']);
        $this->assertSame(0.2, $result['score']);
        Http::assertSentCount(2);

        @unlink($specPath);
    }

    private function writeTinyJpeg(string $path, int $width = 20, int $height = 20): void
    {
        @mkdir(dirname($path), 0777, true);

        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $white);
        imagefilledrectangle($image, 1, (int) ($height * 0.75), $width - 2, $height - 2, $black);
        imagejpeg($image, $path, 90);
        imagedestroy($image);
    }

    private function writeScannedPdf(string $path, int $width, int $height): void
    {
        @mkdir(dirname($path), 0777, true);

        $jpegPath = $path.'.jpg';
        $this->writeTinyJpeg($jpegPath, $width, $height);

        $pdf = new \FPDF('P', 'mm', [50, 70]);
        $pdf->AddPage();
        $pdf->Image($jpegPath, 0, 0, 50, 70);
        $pdf->Output('F', $path);

        @unlink($jpegPath);
    }
}
