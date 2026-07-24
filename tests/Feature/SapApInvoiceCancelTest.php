<?php

namespace Tests\Feature;

use App\Jobs\CancelSapApInvoiceJob;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SapApInvoiceCancelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InvoiceTypeSeeder::class);
    }

    protected function createPostedInvoice(User $user, array $overrides = []): Invoice
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
            'invoice_number' => 'INV-CANCEL-'.uniqid(),
            'faktur_no' => null,
            'invoice_date' => $date,
            'receive_date' => $date,
            'supplier_id' => $supplier->id,
            'po_no' => '12345',
            'currency' => 'IDR',
            'amount' => 1_000_000,
            'type_id' => $typeId,
            'created_by' => $user->id,
            'status' => 'sap',
            'cur_loc' => 'LOC1',
            'payment_status' => 'pending',
            'sap_status' => 'posted',
            'sap_doc_num' => '9001',
            'sap_doc_entry' => '501',
            'sap_doc' => '9001',
        ], $overrides));
    }

    public function test_user_without_cancel_permission_cannot_cancel(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('finance');
        $invoice = $this->createPostedInvoice($user);

        $response = $this->actingAs($user)->post(route('invoices.cancel-sap', $invoice));

        $response->assertForbidden();
    }

    public function test_cancel_blocked_when_not_posted(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('accounting');
        $invoice = $this->createPostedInvoice($user, [
            'sap_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post(route('invoices.cancel-sap', $invoice));

        $response->assertSessionHasErrors('sap_cancel');
        $invoice->refresh();
        $this->assertSame('pending', $invoice->sap_status);
    }

    public function test_cancel_blocked_when_paid(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('accounting');
        $invoice = $this->createPostedInvoice($user, [
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($user)->post(route('invoices.cancel-sap', $invoice));

        $response->assertSessionHasErrors('sap_cancel');
        $invoice->refresh();
        $this->assertSame('posted', $invoice->sap_status);
    }

    public function test_cancel_dispatches_job_and_sets_cancelling(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('accounting');
        $invoice = $this->createPostedInvoice($user);

        $response = $this->actingAs($user)->post(route('invoices.cancel-sap', $invoice));

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame('cancelling', $invoice->sap_status);

        Queue::assertPushed(CancelSapApInvoiceJob::class);
    }

    public function test_cancel_returns_json_for_ajax_requests(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');
        $invoice = $this->createPostedInvoice($user);

        $response = $this->actingAs($user)->postJson(route('invoices.cancel-sap', $invoice));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'sap_status' => 'cancelling',
        ]);
        $response->assertJsonStructure(['status_url', 'invoice_url']);
    }

    public function test_cancel_job_marks_invoice_cancelled_on_success(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createPostedInvoice($user, [
            'sap_status' => 'cancelling',
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('cancelApInvoice')
                ->once()
                ->with('501', Mockery::type('string'))
                ->andReturn([
                    'DocEntry' => 777,
                    'DocNum' => 99001,
                ]);
        });

        (new CancelSapApInvoiceJob($invoice))->handle(app(SapService::class));

        $invoice->refresh();
        $this->assertSame('cancelled', $invoice->sap_status);
        $this->assertSame('99001', $invoice->sap_cancellation_doc_num);
        $this->assertSame('777', $invoice->sap_cancellation_doc_entry);
        $this->assertNotNull($invoice->sap_cancelled_at);
        $this->assertNull($invoice->sap_cancel_error_message);

        $this->assertDatabaseHas('sap_logs', [
            'invoice_id' => $invoice->id,
            'action' => 'cancel_invoice',
            'status' => 'success',
        ]);
    }

    public function test_cancel_job_reverts_to_posted_after_max_retries(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createPostedInvoice($user, [
            'sap_status' => 'cancelling',
        ]);

        $exception = new RequestException(
            'SAP cancel failed',
            new Request('POST', 'PurchaseInvoicesService_Cancel2'),
            new Response(400, [], json_encode([
                'error' => ['message' => ['value' => 'Document cannot be cancelled']],
            ]))
        );

        $this->mock(SapService::class, function ($mock) use ($exception) {
            $mock->shouldReceive('cancelApInvoice')
                ->once()
                ->andThrow($exception);
        });

        $job = Mockery::mock(CancelSapApInvoiceJob::class, [$invoice])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $job->shouldReceive('attempts')->andReturn(3);
        $job->shouldReceive('release')->never();

        try {
            $job->handle(app(SapService::class));
            $this->fail('Expected RequestException was not thrown');
        } catch (RequestException $e) {
            $this->assertSame('SAP cancel failed', $e->getMessage());
        }

        $invoice->refresh();
        $this->assertSame('posted', $invoice->sap_status);
        $this->assertSame('Document cannot be cancelled', $invoice->sap_cancel_error_message);

        $this->assertDatabaseHas('sap_logs', [
            'invoice_id' => $invoice->id,
            'action' => 'cancel_invoice',
            'status' => 'failed',
        ]);
    }

    public function test_sap_status_endpoint_includes_cancel_flags(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('accounting');
        $invoice = $this->createPostedInvoice($user);

        $response = $this->actingAs($user)->getJson(route('invoices.sap-status', $invoice));

        $response->assertOk();
        $response->assertJson([
            'sap_status' => 'posted',
            'is_terminal' => true,
            'show_cancel_button' => true,
            'show_retry_cancel_button' => false,
            'show_send_button' => false,
        ]);
    }

    public function test_repost_allowed_after_cancelled(): void
    {
        Queue::fake();

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('accounting');
        $invoice = $this->createPostedInvoice($user, [
            'sap_status' => 'cancelled',
            'sap_cancellation_doc_num' => '99001',
            'sap_cancellation_doc_entry' => '777',
            'sap_cancelled_at' => now(),
            'po_no' => null,
        ]);

        $statusResponse = $this->actingAs($user)->getJson(route('invoices.sap-status', $invoice));
        $statusResponse->assertJson([
            'show_send_button' => true,
            'show_cancel_button' => false,
        ]);

        $response = $this->actingAs($user)->post(route('invoices.submit-to-sap', $invoice), [
            'grpo_references' => [],
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $invoice->refresh();
        $this->assertSame('pending', $invoice->sap_status);
        Queue::assertPushed(CreateSapApInvoiceJob::class);
    }

    public function test_submit_blocked_while_cancelling(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('accounting');
        $invoice = $this->createPostedInvoice($user, [
            'sap_status' => 'cancelling',
            'po_no' => null,
        ]);

        $response = $this->actingAs($user)->post(route('invoices.submit-to-sap', $invoice), [
            'grpo_references' => [],
        ]);

        $response->assertSessionHasErrors('sap_sync');
        $invoice->refresh();
        $this->assertSame('cancelling', $invoice->sap_status);
    }

    public function test_create_job_does_not_reconcile_against_cancelled_sap_document(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = $this->createPostedInvoice($user, [
            'sap_status' => 'pending',
            'sap_doc_num' => null,
            'sap_doc_entry' => null,
            'sap_doc' => null,
            'po_no' => null,
        ]);
        $invoice->load('supplier');

        $createException = new RequestException(
            'Duplicate or network error',
            new Request('POST', 'PurchaseInvoices'),
            new Response(400, [], json_encode([
                'error' => ['message' => ['value' => 'Create failed']],
            ]))
        );

        $this->mock(SapService::class, function ($mock) use ($invoice, $createException) {
            $mock->shouldReceive('getBusinessPartner')
                ->once()
                ->with($invoice->supplier->sap_code)
                ->andReturn([
                    'CardCode' => $invoice->supplier->sap_code,
                    'CardName' => 'SAP Vendor',
                    'CardType' => 'S',
                ]);

            $mock->shouldReceive('createApInvoice')
                ->once()
                ->andThrow($createException);

            $mock->shouldReceive('get')
                ->once()
                ->withArgs(function (string $endpoint, array $options) use ($invoice) {
                    if ($endpoint !== 'PurchaseInvoices') {
                        return false;
                    }

                    $filter = $options['query']['$filter'] ?? '';

                    return str_contains($filter, $invoice->invoice_number)
                        && str_contains($filter, "Cancelled eq 'N'");
                })
                ->andReturn([
                    'value' => [],
                ]);
        });

        $job = Mockery::mock(CreateSapApInvoiceJob::class, [$invoice, []])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $job->shouldReceive('attempts')->andReturn(3);
        $job->shouldReceive('release')->never();

        try {
            $job->handle(app(SapService::class));
            $this->fail('Expected RequestException was not thrown');
        } catch (RequestException $e) {
            $this->assertSame('Duplicate or network error', $e->getMessage());
        }

        $invoice->refresh();
        $this->assertSame('failed', $invoice->sap_status);
        $this->assertNull($invoice->sap_doc_num);

        $this->assertTrue(
            DB::table('sap_logs')
                ->where('invoice_id', $invoice->id)
                ->where('action', 'create_invoice')
                ->where('status', 'failed')
                ->exists()
        );
    }
}
