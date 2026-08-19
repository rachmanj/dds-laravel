<?php

namespace Tests\Unit;

use App\Services\SignatureMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        Config::set('services.openrouter.model', 'openai/gpt-4o');
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
                                'document_signature_crop' => 'data:image/png;base64,abc',
                                'specimen_signature_crop' => 'data:image/png;base64,def',
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $docPath = storage_path('app/public/attachments/doc.jpg');
        $specPath = storage_path('app/public/signature-specimens/spec.jpg');
        @mkdir(dirname($docPath), 0777, true);
        @mkdir(dirname($specPath), 0777, true);
        file_put_contents($docPath, 'doc');
        file_put_contents($specPath, 'spec');

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

        @unlink($docPath);
        @unlink($specPath);
    }
}
