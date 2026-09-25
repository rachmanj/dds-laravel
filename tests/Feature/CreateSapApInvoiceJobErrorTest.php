<?php

namespace Tests\Feature;

use App\Jobs\CreateSapApInvoiceJob;
use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SapService;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class CreateSapApInvoiceJobErrorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
    }

    public function test_parse_sap_error_message_returns_indonesian_summary_for_502_html_body(): void
    {
        $html = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head><title>502 Proxy Error</title></head><body><h1>Proxy Error</h1></body></html>';
        $exception = new RequestException(
            'Server error',
            new Request('GET', 'https://sap.example/BusinessPartners(\'VCASJIDR01\')'),
            new Response(502, ['Content-Type' => 'text/html'], $html)
        );

        $message = $this->invokeParseSapErrorMessage($exception);

        $this->assertStringNotContainsString('<!DOCTYPE', $message);
        $this->assertStringNotContainsString('<html', strtolower($message));
        $this->assertStringNotContainsString('not found', strtolower($message));
        $this->assertStringNotContainsString('tidak ditemukan', strtolower($message));
        $this->assertStringContainsString('SAP Service Layer gagal merespons', $message);
        $this->assertStringContainsString('502', $message);
        $this->assertStringContainsString('Permintaan akan dicoba ulang otomatis', $message);
        $this->assertLessThanOrEqual(300, mb_strlen($message));
    }

    public function test_resolve_vendor_404_returns_vendor_not_found_in_indonesian(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createInvoiceForJob($user, 'VCASJIDR01');

        $sapService = Mockery::mock(SapService::class);
        $sapService->shouldReceive('getBusinessPartner')
            ->once()
            ->with('VCASJIDR01')
            ->andThrow(new RequestException(
                'Not Found',
                new Request('GET', 'https://sap.example/BusinessPartners(\'VCASJIDR01\')'),
                new Response(404)
            ));

        $job = new CreateSapApInvoiceJob($invoice, []);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('SAP vendor VCASJIDR01 tidak ditemukan di SAP.');

        $this->invokeResolveVendor($job, $sapService, 'VCASJIDR01');
    }

    public function test_resolve_vendor_502_does_not_report_vendor_not_found(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createInvoiceForJob($user, 'VCASJIDR01');

        $html = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><body>502 Proxy Error</body></html>';
        $sapService = Mockery::mock(SapService::class);
        $sapService->shouldReceive('getBusinessPartner')
            ->once()
            ->with('VCASJIDR01')
            ->andThrow(new RequestException(
                'Bad Gateway',
                new Request('GET', 'https://sap.example/BusinessPartners(\'VCASJIDR01\')'),
                new Response(502, [], $html)
            ));

        $job = new CreateSapApInvoiceJob($invoice, []);

        try {
            $this->invokeResolveVendor($job, $sapService, 'VCASJIDR01');
            $this->fail('Expected exception was not thrown.');
        } catch (\Exception $exception) {
            $message = $exception->getMessage();
            $this->assertStringContainsString('Gagal memverifikasi vendor VCASJIDR01 ke SAP:', $message);
            $this->assertStringNotContainsString('tidak ditemukan di SAP', $message);
            $this->assertStringNotContainsString('not found', strtolower($message));
            $this->assertStringNotContainsString('<!DOCTYPE', $message);
            $this->assertLessThanOrEqual(300, mb_strlen($message));
        }
    }

    public function test_parse_sap_error_message_extracts_json_sap_message_value(): void
    {
        $body = json_encode([
            'error' => [
                'message' => [
                    'value' => 'Business partner is blocked for posting.',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $exception = new RequestException(
            'Bad Request',
            new Request('POST', 'https://sap.example/PurchaseInvoices'),
            new Response(400, ['Content-Type' => 'application/json'], $body)
        );

        $message = $this->invokeParseSapErrorMessage($exception);

        $this->assertSame('Business partner is blocked for posting.', $message);
    }

    public function test_parse_sap_error_message_extracts_json_sap_message_string(): void
    {
        $body = json_encode([
            'error' => [
                'message' => 'Invalid Document Total',
            ],
        ], JSON_THROW_ON_ERROR);

        $exception = new RequestException(
            'Bad Request',
            new Request('POST', 'https://sap.example/PurchaseInvoices'),
            new Response(400, ['Content-Type' => 'application/json'], $body)
        );

        $message = $this->invokeParseSapErrorMessage($exception);

        $this->assertSame('Invalid Document Total', $message);
    }

    public function test_parse_sap_error_message_without_response_uses_connection_message(): void
    {
        $exception = new RequestException(
            'Connection timed out',
            new Request('GET', 'https://sap.example/BusinessPartners(\'X\')')
        );

        $message = $this->invokeParseSapErrorMessage($exception);

        $this->assertSame(
            'Tidak dapat terhubung ke SAP Service Layer. Permintaan akan dicoba ulang otomatis.',
            $message
        );
    }

    protected function invokeParseSapErrorMessage(RequestException $exception): string
    {
        $job = new CreateSapApInvoiceJob($this->createMinimalInvoice(), []);
        $method = new ReflectionMethod(CreateSapApInvoiceJob::class, 'parseSapErrorMessage');
        $method->setAccessible(true);

        return $method->invoke($job, $exception);
    }

    /**
     * @return array<string, mixed>
     */
    protected function invokeResolveVendor(CreateSapApInvoiceJob $job, SapService $sapService, string $cardCode): array
    {
        $method = new ReflectionMethod(CreateSapApInvoiceJob::class, 'resolveVendor');
        $method->setAccessible(true);

        return $method->invoke($job, $sapService, $cardCode);
    }

    protected function createInvoiceForJob(User $user, string $sapCode): Invoice
    {
        $typeId = InvoiceType::query()->firstOrFail()->id;
        $supplier = Supplier::query()->create([
            'sap_code' => $sapCode,
            'name' => 'Test Vendor',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $date = now()->toDateString();

        return Invoice::query()->create([
            'invoice_number' => 'INV-ERR-'.uniqid(),
            'faktur_no' => null,
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'po_no' => '12345',
            'currency' => 'IDR',
            'amount' => 1_000_000,
            'type_id' => $typeId,
            'created_by' => $user->id,
            'status' => 'verified',
            'cur_loc' => 'LOC1',
            'payment_status' => 'pending',
            'sap_status' => 'pending',
        ]);
    }

    protected function createMinimalInvoice(): Invoice
    {
        $user = User::factory()->create(['is_active' => true]);

        return $this->createInvoiceForJob($user, 'V-MIN');
    }
}
