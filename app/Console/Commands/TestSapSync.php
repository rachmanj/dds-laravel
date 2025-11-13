<?php

namespace App\Console\Commands;

use App\Jobs\SyncSapItoDocumentsJob;
use App\Services\SapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestSapSync extends Command
{
    protected $signature = 'sap:test-sync {start_date} {end_date} {--sync}';
    
    protected $description = 'Test SAP ITO sync - fetch data and optionally sync to database';

    public function handle()
    {
        $startDate = $this->argument('start_date');
        $endDate = $this->argument('end_date');
        $shouldSync = $this->option('sync');
        
        $this->info("Testing SAP ITO Sync");
        $this->info("Date Range: {$startDate} to {$endDate}");
        $this->newLine();
        
        try {
            $sapService = app(SapService::class);
            
            // Ensure we're logged in first
            $this->info("0. Logging in to SAP...");
            try {
                $sapService->login();
                $this->info("   ✓ Login successful!");
            } catch (\Exception $e) {
                $this->error("   ✗ Login failed: " . $e->getMessage());
                return 1;
            }
            $this->newLine();
            
            // Test direct entity query
            $this->info("1. Testing direct entity query...");
            
            // First, try to see what date range has data
            $this->info("   Checking for available data (no date filter)...");
            try {
                $client = $sapService->getClient();
                $allDataResponse = $client->get('InventoryTransferRequests', [
                    'query' => [
                        '$top' => 5,
                        '$orderby' => 'CreationDate desc'
                    ]
                ]);
                $allData = json_decode($allDataResponse->getBody()->getContents(), true);
                if (isset($allData['value']) && count($allData['value']) > 0) {
                    $this->info("   Found " . count($allData['value']) . " recent records (any date)");
                    $this->info("   Most recent record dates:");
                    foreach (array_slice($allData['value'], 0, 3) as $rec) {
                        $date = $rec['CreationDate'] ?? $rec['DocDate'] ?? 'N/A';
                        $docNum = $rec['DocNum'] ?? 'N/A';
                        $this->line("     - DocNum: {$docNum}, Date: {$date}");
                    }
                } else {
                    $this->warn("   No records found in InventoryTransferRequests");
                }
            } catch (\Exception $e) {
                $this->warn("   Could not check available data: " . $e->getMessage());
            }
            $this->newLine();
            
            $this->info("   Querying with date filter: {$startDate} to {$endDate}...");
            
            // Try querying with different date fields and filters to see which one works
            $this->info("   Testing different date fields and filters...");
            $dateFieldsToTest = ['DocDate', 'CreationDate'];
            $workingField = null;
            $workingFilter = null;
            
            foreach ($dateFieldsToTest as $dateField) {
                try {
                    $client = $sapService->getClient();
                    $startDateTime = $startDate . 'T00:00:00';
                    $endDateTime = $endDate . 'T23:59:59';
                    
                    // Test 1: Without TransferType filter
                    $testFilter = "{$dateField} ge datetime'{$startDateTime}' and {$dateField} le datetime'{$endDateTime}'";
                    $this->line("     Testing with {$dateField} (no TransferType filter)...");
                    $testResponse = $client->get('InventoryTransferRequests', [
                        'query' => [
                            '$filter' => $testFilter,
                            '$top' => 5
                        ]
                    ]);
                    $testResult = json_decode($testResponse->getBody()->getContents(), true);
                    $count = isset($testResult['value']) ? count($testResult['value']) : 0;
                    $this->line("       Found {$count} records");
                    
                    if ($count > 0) {
                        $workingField = $dateField;
                        $workingFilter = $testFilter;
                        $this->info("     ✓ {$dateField} works without TransferType filter!");
                        
                        // Test 2: With TransferType filter
                        $testFilterWithType = $testFilter . " and U_MIS_TransferType eq 'OUT'";
                        $this->line("     Testing with {$dateField} + TransferType='OUT' filter...");
                        try {
                            $testResponse2 = $client->get('InventoryTransferRequests', [
                                'query' => [
                                    '$filter' => $testFilterWithType,
                                    '$top' => 5
                                ]
                            ]);
                            $testResult2 = json_decode($testResponse2->getBody()->getContents(), true);
                            $count2 = isset($testResult2['value']) ? count($testResult2['value']) : 0;
                            $this->line("       Found {$count2} records with TransferType='OUT'");
                            if ($count2 > 0) {
                                $workingFilter = $testFilterWithType;
                                $this->info("     ✓ TransferType filter also works!");
                            } else {
                                $this->warn("     ⚠ No records with TransferType='OUT' - will use filter without it");
                            }
                        } catch (\Exception $e) {
                            $this->warn("     ⚠ TransferType filter failed: " . substr($e->getMessage(), 0, 50));
                        }
                        break;
                    }
                } catch (\Exception $e) {
                    $this->line("       Error: " . substr($e->getMessage(), 0, 60));
                }
            }
            $this->newLine();
            
            $results = $sapService->getStockTransferRequests($startDate, $endDate);
            $this->info("   ✓ Found " . count($results) . " records");
            
            if (count($results) > 0) {
                $this->info("   Sample record fields:");
                $sample = $results[0];
                foreach (array_slice(array_keys($sample), 0, 15) as $key) {
                    $value = is_array($sample[$key]) ? '[array]' : (is_object($sample[$key]) ? '[object]' : $sample[$key]);
                    $this->line("     - {$key}: " . substr((string)$value, 0, 50));
                }
            } else {
                $this->warn("   ⚠ No records found for this date range. Try a wider date range or check if data exists.");
            }
            $this->newLine();
            
            if ($shouldSync) {
                $this->info("2. Dispatching sync job...");
                
                // Set a default user for the job (if needed)
                if (!Auth::check()) {
                    // Try to get first admin user
                    $admin = \App\Models\User::whereHas('roles', function($q) {
                        $q->whereIn('name', ['admin', 'superadmin']);
                    })->first();
                    
                    if ($admin) {
                        Auth::login($admin);
                        $this->info("   Using user: {$admin->name}");
                    }
                }
                
                // For testing, run synchronously to see immediate results
                // In production, use dispatch() to queue it
                $job = new SyncSapItoDocumentsJob($startDate, $endDate);
                $job->handle(app(SapService::class));
                
                $this->info("   ✓ Sync completed!");
                $this->info("   Check logs at: storage/logs/sap-" . date('Y-m-d') . ".log");
                $this->info("   Check sap_logs table for sync results");
            } else {
                $this->info("2. Skipping sync (use --sync flag to actually sync)");
                $this->info("   To sync, run: php artisan sap:test-sync {$startDate} {$endDate} --sync");
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}
