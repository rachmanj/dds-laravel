<?php

namespace App\Console\Commands;

use App\Services\SapService;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SapReconcile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:reconcile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile SAP invoice statuses with local database';

    /**
     * Execute the console command.
     */
    public function handle(SapService $sapService)
    {
        $this->info('Starting SAP reconciliation...');

        try {
            // Query SAP for recent invoices (last 7 days, adjust as needed)
            $recentDate = now()->subDays(7)->format('Y-m-d');
            $sapInvoices = $sapService->getRecentInvoices($recentDate); // Add this method to SapService

            $localPending = Invoice::where('sap_status', 'pending')->get();

            foreach ($localPending as $invoice) {
                $matchingSap = collect($sapInvoices)->firstWhere('Comments', 'like', '%Invoice #' . $invoice->id . '%');

                if ($matchingSap) {
                    $invoice->update([
                        'sap_status' => 'posted',
                        'sap_doc_num' => $matchingSap['DocNum'],
                    ]);
                    $this->info("Reconciled invoice {$invoice->id} to SAP DocNum {$matchingSap['DocNum']}");
                }
            }

            DB::table('sap_logs')->insert([
                'action' => 'reconcile',
                'status' => 'success',
                'response_payload' => json_encode(['reconciled' => $localPending->count()]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info('Reconciliation complete.');
        } catch (\Exception $e) {
            $this->error('Reconciliation failed: ' . $e->getMessage());
            DB::table('sap_logs')->insert([
                'action' => 'reconcile',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
