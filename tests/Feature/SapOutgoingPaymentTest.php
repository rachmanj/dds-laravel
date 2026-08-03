<?php

namespace Tests\Feature;

use App\Jobs\CreateSapOutgoingPaymentJob;
use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SapApInvoicePaymentResolver;
use App\Services\SapOutgoingPaymentPayloadBuilder;
use App\Services\SapService;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SapOutgoingPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
    }

    protected function createPaymentReadyInvoice(User $user, array $overrides = []): Invoice
    {
        $typeId = InvoiceType::query()->firstOrFail()->id;
        $supplier = Supplier::query()->create([
            'sap_code' => 'V-SAP',
            'name' => 'SAP Vendor',
            'type' => 'vendor',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $date = now()->toDateString();

        return Invoice::query()->create(array_merge([
            'invoice_number' => 'INV-PAY-'.uniqid(),
            'faktur_no' => null,
            'invoice_date' => $date,
            'receive_date' => $date,
            'payment_date' => $date,
            'payment_status' => 'paid',
            'paid_by' => $user->id,
            'paid_at' => now(),
            'supplier_id' => $supplier->id,
            'po_no' => '12345',
            'currency' => 'IDR',
            'amount' => 1_000_000,
            'type_id' => $typeId,
            'created_by' => $user->id,
            'status' => 'sap',
            'cur_loc' => 'LOC1',
            'sap_status' => 'posted',
            'sap_doc_num' => '1001',
            'sap_doc_entry' => '456',
        ], $overrides));
    }

    protected function mockResolvableApInvoice(Invoice $invoice, int $docEntry = 456, int|string $docNum = 1001): void
    {
        $this->mock(SapApInvoicePaymentResolver::class, function ($mock) use ($invoice, $docEntry, $docNum) {
            $mock->shouldReceive('resolve')
                ->with(\Mockery::on(fn ($arg) => $arg->is($invoice)), \Mockery::type(SapService::class))
                ->andReturn([
                    'DocEntry' => $docEntry,
                    'DocNum' => $docNum,
                    'CardCode' => $invoice->supplier->sap_code ?? 'V-SAP',
                    'DocumentStatus' => 'bost_Open',
                    'Cancelled' => 'tNO',
                ]);
        });
    }

    public function test_preview_blocked_when_ap_invoice_not_posted(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createPaymentReadyInvoice($user, [
            'sap_status' => null,
            'sap_doc_entry' => null,
            'sap_doc_num' => null,
        ]);

        $response = $this->actingAs($user)->get(route('invoices.payment-sap-preview', $invoice));

        $response->assertRedirect(route('invoices.payments.paid'));
        $response->assertSessionHasErrors('sap_payment');
    }

    public function test_preview_blocked_when_invoice_not_paid_locally(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createPaymentReadyInvoice($user, [
            'payment_status' => 'pending',
            'paid_by' => null,
            'paid_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('invoices.payment-sap-preview', $invoice));

        $response->assertRedirect(route('invoices.payments.paid'));
        $response->assertSessionHasErrors('sap_payment');
    }

    public function test_preview_page_loads_for_eligible_invoice(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createPaymentReadyInvoice($user);
        $this->mockResolvableApInvoice($invoice);

        $response = $this->actingAs($user)->get(route('invoices.payment-sap-preview', $invoice));

        $response->assertOk();
        $response->assertSee('SAP Outgoing Payment Preview');
        $response->assertSee('456');
        $response->assertSee('it_PurchaseInvoice');
    }

    public function test_submit_validates_payment_means_and_account_code(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createPaymentReadyInvoice($user);
        $this->mockResolvableApInvoice($invoice);

        $response = $this->actingAs($user)->post(route('invoices.submit-payment-to-sap', $invoice), []);

        $response->assertSessionHasErrors(['payment_means', 'account_code']);
    }

    public function test_submit_dispatches_job_and_sets_pending_status(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createPaymentReadyInvoice($user);
        $this->mockResolvableApInvoice($invoice);

        $response = $this->actingAs($user)->post(route('invoices.submit-payment-to-sap', $invoice), [
            'payment_means' => 'transfer',
            'account_code' => '120030',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('invoices.payments.paid'));
        $response->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame('pending', $invoice->sap_payment_status);
        $this->assertSame('transfer', $invoice->sap_payment_means);
        $this->assertSame('120030', $invoice->sap_payment_account_code);

        Queue::assertPushed(CreateSapOutgoingPaymentJob::class);
    }

    public function test_payload_builder_builds_transfer_payment_payload(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createPaymentReadyInvoice($user);

        $builder = new SapOutgoingPaymentPayloadBuilder($invoice, 'transfer', '120030', 456, '2026-07-31');
        $payload = $builder->build();

        $this->assertSame('rSupplier', $payload['DocType']);
        $this->assertSame('V-SAP', $payload['CardCode']);
        $this->assertSame('2026-07-31', $payload['DocDate']);
        $this->assertSame('IDR', $payload['DocCurrency']);
        $this->assertSame('120030', $payload['TransferAccount']);
        $this->assertSame(1000000.0, $payload['TransferSum']);
        $this->assertSame('2026-07-31', $payload['TransferDate']);
        $this->assertSame($invoice->invoice_number, $payload['TransferReference']);
        $this->assertArrayNotHasKey('CashAccount', $payload);
        $this->assertCount(1, $payload['PaymentInvoices']);
        $this->assertSame(456, $payload['PaymentInvoices'][0]['DocEntry']);
        $this->assertSame(1000000.0, $payload['PaymentInvoices'][0]['SumApplied']);
        $this->assertSame('it_PurchaseInvoice', $payload['PaymentInvoices'][0]['InvoiceType']);
        $this->assertSame(1, $payload['PaymentInvoices'][0]['InstallmentId']);
    }

    public function test_payload_builder_builds_cash_payment_payload(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createPaymentReadyInvoice($user);

        $builder = new SapOutgoingPaymentPayloadBuilder($invoice, 'cash', '110020', 456);
        $payload = $builder->build();

        $this->assertSame('110020', $payload['CashAccount']);
        $this->assertSame(1000000.0, $payload['CashSum']);
        $this->assertArrayNotHasKey('TransferAccount', $payload);
    }

    public function test_job_success_updates_invoice_and_logs(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createPaymentReadyInvoice($user, [
            'sap_payment_status' => 'pending',
            'sap_payment_means' => 'transfer',
            'sap_payment_account_code' => '120030',
        ]);

        $this->mock(SapApInvoicePaymentResolver::class, function ($mock) {
            $mock->shouldReceive('resolve')
                ->once()
                ->andReturn([
                    'DocEntry' => 456,
                    'DocNum' => 1001,
                    'CardCode' => 'V-SAP',
                    'DocumentStatus' => 'bost_Open',
                    'Cancelled' => 'tNO',
                ]);
        });

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('createOutgoingPayment')
                ->once()
                ->andReturn([
                    'DocEntry' => 789,
                    'DocNum' => 2001,
                ]);
        });

        $job = new CreateSapOutgoingPaymentJob($invoice, 'transfer', '120030', now()->toDateString());
        $job->handle(app(SapService::class), app(SapApInvoicePaymentResolver::class));

        $invoice->refresh();
        $this->assertSame('posted', $invoice->sap_payment_status);
        $this->assertSame('2001', $invoice->sap_payment_doc_num);
        $this->assertSame('789', $invoice->sap_payment_doc_entry);
        $this->assertNull($invoice->sap_payment_error_message);

        $log = DB::table('sap_logs')
            ->where('invoice_id', $invoice->id)
            ->where('action', 'create_payment')
            ->where('status', 'success')
            ->first();

        $this->assertNotNull($log);
    }

    public function test_job_failure_sets_failed_status_and_logs(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createPaymentReadyInvoice($user, [
            'sap_payment_status' => 'pending',
            'sap_payment_means' => 'transfer',
            'sap_payment_account_code' => '120030',
        ]);

        $this->mock(SapApInvoicePaymentResolver::class, function ($mock) {
            $mock->shouldReceive('resolve')
                ->once()
                ->andReturn([
                    'DocEntry' => 456,
                    'DocNum' => 1001,
                    'CardCode' => 'V-SAP',
                    'DocumentStatus' => 'bost_Open',
                    'Cancelled' => 'tNO',
                ]);
        });

        $response = new Response(400, [], json_encode([
            'error' => ['message' => ['value' => 'Invalid account code']],
        ]));
        $exception = RequestException::create(new Request('POST', 'VendorPayments'), $response);

        $this->mock(SapService::class, function ($mock) use ($exception) {
            $mock->shouldReceive('createOutgoingPayment')
                ->once()
                ->andThrow($exception);
        });

        $job = new CreateSapOutgoingPaymentJob($invoice, 'transfer', '120030', now()->toDateString());

        try {
            $job->handle(app(SapService::class), app(SapApInvoicePaymentResolver::class));
            $this->fail('Expected exception was not thrown');
        } catch (RequestException) {
            // expected
        }

        $invoice->refresh();
        $this->assertSame('failed', $invoice->sap_payment_status);
        $this->assertSame('Invalid account code', $invoice->sap_payment_error_message);

        $log = DB::table('sap_logs')
            ->where('invoice_id', $invoice->id)
            ->where('action', 'create_payment')
            ->where('status', 'failed')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Invalid account code', $log->error_message);
    }

    public function test_payment_sap_status_returns_json(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createPaymentReadyInvoice($user, [
            'sap_payment_status' => 'posted',
            'sap_payment_doc_num' => '2001',
            'sap_payment_doc_entry' => '789',
        ]);

        $response = $this->actingAs($user)->getJson(route('invoices.payment-sap-status', $invoice));

        $response->assertOk();
        $response->assertJson([
            'sap_status' => 'posted',
            'sap_doc_num' => '2001',
            'is_terminal' => true,
        ]);
    }
}
