<?php

namespace App\Console\Commands;

use App\Services\SapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestSqlServerConnection extends Command
{
    protected $signature = 'sap:test-sql-connection {--date=2025-11-13 : Date to test (Y-m-d format)}';
    protected $description = 'Test SQL Server connection and ITO query accuracy';

    public function handle()
    {
        $testDate = $this->option('date');
        
        $this->info('=== SQL Server Connection Test ===');
        $this->newLine();

        // Test 1: Check PHP extensions
        $this->info('1. PHP Extension Check:');
        if (extension_loaded('sqlsrv')) {
            $this->info('   ✓ sqlsrv extension is loaded (version: ' . phpversion('sqlsrv') . ')');
        } else {
            $this->error('   ✗ sqlsrv extension is NOT loaded');
            $this->error('   Please install the sqlsrv PHP extension');
            return 1;
        }

        if (extension_loaded('pdo_sqlsrv')) {
            $this->info('   ✓ pdo_sqlsrv extension is loaded (version: ' . phpversion('pdo_sqlsrv') . ')');
        } else {
            $this->warn('   ⚠ pdo_sqlsrv extension is NOT loaded');
        }
        $this->newLine();

        // Test 2: Check configuration
        $this->info('2. Database Configuration:');
        $config = config('database.connections.sap_sql');
        if (!$config) {
            $this->error('   ✗ sap_sql connection not configured');
            return 1;
        }

        $this->line('   Host: ' . ($config['host'] ?? 'not set'));
        $this->line('   Port: ' . ($config['port'] ?? 'not set'));
        $this->line('   Database: ' . ($config['database'] ?? 'not set'));
        $this->line('   Username: ' . ($config['username'] ?? 'not set'));
        $this->line('   Password: ' . (isset($config['password']) && $config['password'] ? '***set***' : 'not set'));
        $this->line('   Driver: ' . ($config['driver'] ?? 'not set'));
        $this->newLine();

        // Test 3: Test connection
        $this->info('3. Connection Test:');
        try {
            $connection = DB::connection('sap_sql');
            $pdo = $connection->getPdo();
            $this->info('   ✓ Connection successful');
            $this->line('   Server version: ' . $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION));
        } catch (\Exception $e) {
            $this->error('   ✗ Connection failed: ' . $e->getMessage());
            $this->error('   Please check:');
            $this->error('     - Network connectivity to SQL Server');
            $this->error('     - Firewall rules');
            $this->error('     - SQL Server credentials');
            $this->error('     - SQL Server is running and accepting connections');
            return 1;
        }
        $this->newLine();

        // Test 4: Simple query
        $this->info('4. Simple Query Test:');
        try {
            $result = DB::connection('sap_sql')->select("SELECT @@VERSION as version, GETDATE() as [current_date]");
            if (count($result) > 0) {
                $this->info('   ✓ Query executed successfully');
                $this->line('   SQL Server Date: ' . $result[0]->current_date);
            }
        } catch (\Exception $e) {
            $this->error('   ✗ Query failed: ' . $e->getMessage());
            return 1;
        }
        $this->newLine();

        // Test 5: ITO query test
        $this->info("5. ITO Query Test (Date: {$testDate}):");
        $startDateTime = $testDate . ' 00:00:00';
        $endDateTime = $testDate . ' 23:59:59';

        try {
            // Count query
            $countSql = "
                SELECT COUNT(DISTINCT T0.[DocNum]) as total
                FROM OWTR T0 
                INNER JOIN WTR1 T1 ON T0.[DocEntry] = T1.[DocEntry]
                INNER JOIN OITW T2 ON T1.[ItemCode] = T2.[ItemCode]
                WHERE 
                    T0.[CreateDate] >= ? 
                    AND T0.[CreateDate] <= ? 
                    AND T2.[WhsCode] = T0.[Filler] 
                    AND T0.[U_MIS_TransferType] = 'OUT'
            ";

            $countResult = DB::connection('sap_sql')->select($countSql, [$startDateTime, $endDateTime]);
            $totalCount = $countResult[0]->total ?? 0;
            $this->line("   Count query result: {$totalCount} records");

            if ($totalCount == 0) {
                $this->warn('   ⚠ No records found. Checking possible issues...');
                $this->newLine();

                // Check without date filter
                $countNoDate = "
                    SELECT COUNT(DISTINCT T0.[DocNum]) as total
                    FROM OWTR T0 
                    INNER JOIN WTR1 T1 ON T0.[DocEntry] = T1.[DocEntry]
                    INNER JOIN OITW T2 ON T1.[ItemCode] = T2.[ItemCode]
                    WHERE 
                        T2.[WhsCode] = T0.[Filler] 
                        AND T0.[U_MIS_TransferType] = 'OUT'
                ";
                $countNoDateResult = DB::connection('sap_sql')->select($countNoDate);
                $totalNoDate = $countNoDateResult[0]->total ?? 0;
                $this->line("   Total ITO records (no date filter): {$totalNoDate}");

                // Check date range
                $dateRangeSql = "
                    SELECT 
                        MIN(T0.[CreateDate]) as min_date,
                        MAX(T0.[CreateDate]) as max_date,
                        COUNT(DISTINCT T0.[DocNum]) as total
                    FROM OWTR T0 
                    INNER JOIN WTR1 T1 ON T0.[DocEntry] = T1.[DocEntry]
                    INNER JOIN OITW T2 ON T1.[ItemCode] = T2.[ItemCode]
                    WHERE 
                        T2.[WhsCode] = T0.[Filler] 
                        AND T0.[U_MIS_TransferType] = 'OUT'
                ";
                $dateRangeResult = DB::connection('sap_sql')->select($dateRangeSql);
                if (count($dateRangeResult) > 0) {
                    $this->line("   Date range in database:");
                    $this->line("     Min: " . ($dateRangeResult[0]->min_date ?? 'NULL'));
                    $this->line("     Max: " . ($dateRangeResult[0]->max_date ?? 'NULL'));
                }

                // Check if date format is the issue
                $this->newLine();
                $this->line("   Testing different date formats...");
                $dateFormats = [
                    ['2025-11-13 00:00:00', '2025-11-13 23:59:59'],
                    ['2025-11-13T00:00:00', '2025-11-13T23:59:59'],
                    ['20251113', '20251113'],
                ];

                foreach ($dateFormats as $format) {
                    try {
                        $testCount = DB::connection('sap_sql')->select($countSql, $format);
                        $testTotal = $testCount[0]->total ?? 0;
                        $this->line("     Format [" . implode(', ', $format) . "]: {$testTotal} records");
                    } catch (\Exception $e) {
                        $this->line("     Format [" . implode(', ', $format) . "]: Error - " . $e->getMessage());
                    }
                }
            }

            // Full query test
            $this->newLine();
            $this->line("   Testing full query...");
            $sapService = app(SapService::class);
            $fullResults = $sapService->executeItoSqlQuery($testDate, $testDate);
            $fullCount = count($fullResults);
            $this->line("   Full query result: {$fullCount} records");

            if ($fullCount > 0 && $fullCount < 10) {
                $this->line("   Sample records:");
                foreach (array_slice($fullResults, 0, 3) as $idx => $row) {
                    $this->line("     Record " . ($idx + 1) . ":");
                    $this->line("       ITO No: " . ($row['ito_no'] ?? 'NULL'));
                    $this->line("       CreateDate: " . ($row['ito_created_date'] ?? 'NULL'));
                    $this->line("       DocDate: " . ($row['ito_date'] ?? 'NULL'));
                }
            }

            // Compare counts
            if ($totalCount != $fullCount) {
                $this->warn("   ⚠ WARNING: Count mismatch!");
                $this->warn("     Count query: {$totalCount}");
                $this->warn("     Full query: {$fullCount}");
            } else {
                $this->info("   ✓ Counts match");
            }

            // Expected count comparison
            $this->newLine();
            if ($fullCount == 34) {
                $this->info("   ✓ Matches expected count (34 records for 2025-11-13)");
            } elseif ($fullCount == 3) {
                $this->warn("   ⚠ Only 3 records found (expected 34)");
                $this->warn("   Possible issues:");
                $this->warn("     - Different database/company code");
                $this->warn("     - Date timezone mismatch");
                $this->warn("     - Data not yet synced to this database");
                $this->warn("     - Different date format in database");
            } else {
                $this->line("   Found {$fullCount} records (expected 34 for 2025-11-13)");
            }

        } catch (\Exception $e) {
            $this->error('   ✗ Query failed: ' . $e->getMessage());
            $this->error('   Stack trace:');
            $this->error($e->getTraceAsString());
            return 1;
        }

        $this->newLine();
        $this->info('=== Test Complete ===');
        
        return 0;
    }
}
