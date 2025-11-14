<?php
/**
 * Standalone SQL Server Connection Test Script
 * 
 * Run this on production server to diagnose connection issues:
 * php test_sql_production.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\SapService;

$testDate = '2025-11-13';
$startDateTime = $testDate . ' 00:00:00';
$endDateTime = $testDate . ' 23:59:59';

echo "=== Production SQL Server Diagnostic Test ===" . PHP_EOL . PHP_EOL;

// Test 1: Connection
echo "1. Testing Connection..." . PHP_EOL;
try {
    $connection = DB::connection('sap_sql');
    $pdo = $connection->getPdo();
    echo "   ✓ Connected to: " . $pdo->getAttribute(\PDO::ATTR_SERVER_INFO) . PHP_EOL;
    echo "   Server: " . config('database.connections.sap_sql.host') . PHP_EOL;
    echo "   Database: " . config('database.connections.sap_sql.database') . PHP_EOL;
} catch (\Exception $e) {
    echo "   ✗ Connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// Test 2: Simple count query
echo "2. Testing Count Query..." . PHP_EOL;
try {
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
    echo "   Result: {$totalCount} records" . PHP_EOL;
    
    if ($totalCount == 0) {
        echo "   ⚠ No records found!" . PHP_EOL;
    } elseif ($totalCount < 10) {
        echo "   ⚠ Only {$totalCount} records found (expected ~34)" . PHP_EOL;
    } else {
        echo "   ✓ Found {$totalCount} records" . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "   ✗ Query failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// Test 3: Check date range in database
echo "3. Checking Date Range in Database..." . PHP_EOL;
try {
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
        echo "   Min CreateDate: " . ($dateRangeResult[0]->min_date ?? 'NULL') . PHP_EOL;
        echo "   Max CreateDate: " . ($dateRangeResult[0]->max_date ?? 'NULL') . PHP_EOL;
        echo "   Total ITO records: " . ($dateRangeResult[0]->total ?? 0) . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "   ✗ Query failed: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Test 4: Test without date filter
echo "4. Testing Without Date Filter..." . PHP_EOL;
try {
    $noDateSql = "
        SELECT COUNT(DISTINCT T0.[DocNum]) as total
        FROM OWTR T0 
        INNER JOIN WTR1 T1 ON T0.[DocEntry] = T1.[DocEntry]
        INNER JOIN OITW T2 ON T1.[ItemCode] = T2.[ItemCode]
        WHERE 
            T2.[WhsCode] = T0.[Filler] 
            AND T0.[U_MIS_TransferType] = 'OUT'
    ";
    $noDateResult = DB::connection('sap_sql')->select($noDateSql);
    $noDateTotal = $noDateResult[0]->total ?? 0;
    echo "   Total ITO records (all dates): {$noDateTotal}" . PHP_EOL;
} catch (\Exception $e) {
    echo "   ✗ Query failed: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Test 5: Test with different date formats
echo "5. Testing Different Date Formats..." . PHP_EOL;
$dateFormats = [
    ['2025-11-13 00:00:00', '2025-11-13 23:59:59', 'Standard format'],
    ['2025-11-13T00:00:00', '2025-11-13T23:59:59', 'ISO format'],
    ['2025-11-13', '2025-11-13', 'Date only'],
];

foreach ($dateFormats as $format) {
    try {
        $testCount = DB::connection('sap_sql')->select($countSql, [$format[0], $format[1]]);
        $testTotal = $testCount[0]->total ?? 0;
        echo "   {$format[2]}: {$testTotal} records" . PHP_EOL;
    } catch (\Exception $e) {
        echo "   {$format[2]}: Error - " . substr($e->getMessage(), 0, 60) . PHP_EOL;
    }
}

echo PHP_EOL;

// Test 6: Full query test
echo "6. Testing Full Query via SapService..." . PHP_EOL;
try {
    $sapService = app(SapService::class);
    $fullResults = $sapService->executeItoSqlQuery($testDate, $testDate);
    $fullCount = count($fullResults);
    echo "   Result: {$fullCount} records" . PHP_EOL;
    
    if ($fullCount > 0 && $fullCount < 10) {
        echo "   Sample records:" . PHP_EOL;
        foreach (array_slice($fullResults, 0, 3) as $idx => $row) {
            echo "     Record " . ($idx + 1) . ": ITO " . ($row['ito_no'] ?? 'NULL') . 
                 " (CreateDate: " . substr($row['ito_created_date'] ?? 'NULL', 0, 10) . ")" . PHP_EOL;
        }
    }
} catch (\Exception $e) {
    echo "   ✗ Query failed: " . $e->getMessage() . PHP_EOL;
    echo "   Stack trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL;

// Test 7: Check for specific ITO numbers
echo "7. Checking for Specific ITO Numbers..." . PHP_EOL;
$testItoNumbers = ['251006949', '251006950', '251006952'];
foreach ($testItoNumbers as $itoNo) {
    try {
        $itoSql = "
            SELECT T0.[DocNum], T0.[CreateDate], T0.[DocDate], T0.[U_MIS_TransferType]
            FROM OWTR T0
            WHERE T0.[DocNum] = ?
        ";
        $itoResult = DB::connection('sap_sql')->select($itoSql, [$itoNo]);
        if (count($itoResult) > 0) {
            $ito = $itoResult[0];
            echo "   ITO {$itoNo}: Found" . PHP_EOL;
            echo "     CreateDate: " . ($ito->CreateDate ?? 'NULL') . PHP_EOL;
            echo "     DocDate: " . ($ito->DocDate ?? 'NULL') . PHP_EOL;
            echo "     TransferType: " . ($ito->U_MIS_TransferType ?? 'NULL') . PHP_EOL;
        } else {
            echo "   ITO {$itoNo}: Not found" . PHP_EOL;
        }
    } catch (\Exception $e) {
        echo "   ITO {$itoNo}: Error - " . substr($e->getMessage(), 0, 60) . PHP_EOL;
    }
}

echo PHP_EOL;
echo "=== Diagnostic Complete ===" . PHP_EOL;
echo PHP_EOL;
echo "If you see only 3 records, possible causes:" . PHP_EOL;
echo "  1. Different database/company code" . PHP_EOL;
echo "  2. Date timezone mismatch" . PHP_EOL;
echo "  3. Data not yet synced to this database" . PHP_EOL;
echo "  4. Different date format in database" . PHP_EOL;
echo "  5. Network/firewall limiting query results" . PHP_EOL;

