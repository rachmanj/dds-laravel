<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== SQL Server Connection Test ===" . PHP_EOL . PHP_EOL;

// Test 1: Check if sqlsrv extension is loaded
echo "1. PHP Extension Check:" . PHP_EOL;
if (extension_loaded('sqlsrv')) {
    echo "   ✓ sqlsrv extension is loaded" . PHP_EOL;
    $version = phpversion('sqlsrv');
    echo "   Version: {$version}" . PHP_EOL;
} else {
    echo "   ✗ sqlsrv extension is NOT loaded" . PHP_EOL;
    echo "   Please install the sqlsrv PHP extension" . PHP_EOL;
    exit(1);
}

if (extension_loaded('pdo_sqlsrv')) {
    echo "   ✓ pdo_sqlsrv extension is loaded" . PHP_EOL;
    $pdoVersion = phpversion('pdo_sqlsrv');
    echo "   Version: {$pdoVersion}" . PHP_EOL;
} else {
    echo "   ✗ pdo_sqlsrv extension is NOT loaded" . PHP_EOL;
}

echo PHP_EOL;

// Test 2: Check database configuration
echo "2. Database Configuration:" . PHP_EOL;
$config = config('database.connections.sap_sql');
if ($config) {
    echo "   Host: " . ($config['host'] ?? 'not set') . PHP_EOL;
    echo "   Port: " . ($config['port'] ?? 'not set') . PHP_EOL;
    echo "   Database: " . ($config['database'] ?? 'not set') . PHP_EOL;
    echo "   Username: " . ($config['username'] ?? 'not set') . PHP_EOL;
    echo "   Password: " . (isset($config['password']) && $config['password'] ? '***set***' : 'not set') . PHP_EOL;
    echo "   Driver: " . ($config['driver'] ?? 'not set') . PHP_EOL;
} else {
    echo "   ✗ sap_sql connection not configured" . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// Test 3: Test connection
echo "3. Connection Test:" . PHP_EOL;
try {
    $connection = DB::connection('sap_sql');
    $pdo = $connection->getPdo();
    echo "   ✓ Connection successful" . PHP_EOL;
    echo "   Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . PHP_EOL;
} catch (\Exception $e) {
    echo "   ✗ Connection failed: " . $e->getMessage() . PHP_EOL;
    echo "   Error details:" . PHP_EOL;
    echo "   " . $e->getTraceAsString() . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// Test 4: Simple query test
echo "4. Simple Query Test:" . PHP_EOL;
try {
    $result = DB::connection('sap_sql')->select("SELECT @@VERSION as version, GETDATE() as current_date");
    if (count($result) > 0) {
        echo "   ✓ Query executed successfully" . PHP_EOL;
        echo "   SQL Server Date: " . $result[0]->current_date . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "   ✗ Query failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// Test 5: Test ITO query with date 2025-11-13
echo "5. ITO Query Test (2025-11-13):" . PHP_EOL;
$testDate = '2025-11-13';
$startDateTime = $testDate . ' 00:00:00';
$endDateTime = $testDate . ' 23:59:59';

try {
    // First, test a simple count query
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
    echo "   Total ITO records: {$totalCount}" . PHP_EOL;
    
    if ($totalCount == 0) {
        echo "   ⚠ No records found. Checking date filter..." . PHP_EOL;
        
        // Test without date filter
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
        echo "   Total ITO records (no date filter): {$totalNoDate}" . PHP_EOL;
        
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
            echo "   Date range in database:" . PHP_EOL;
            echo "     Min: " . ($dateRangeResult[0]->min_date ?? 'NULL') . PHP_EOL;
            echo "     Max: " . ($dateRangeResult[0]->max_date ?? 'NULL') . PHP_EOL;
        }
    }
    
    // Now test the full query
    $fullSql = "
        SELECT DISTINCT
            T0.[DocNum] AS ito_no, 
            T0.[DocDate] AS ito_date, 
            T0.[CreateDate] AS ito_created_date,
            T11.[USER_CODE] AS ito_created_by,
            T11.[U_NAME],
            T0.[Printed],
            T4.[DocNum] AS grpo_no,
            T6.[DocNum] AS po_no,
            T6.[CardCode] AS vendor_code,
            T0.[Filler] AS origin_whs, 
            T0.[U_MIS_ToWarehouse] AS destination_whs,
            T0.[Comments] AS ito_remarks,
            T3.[DocNum] AS iti_no,
            T3.[DocDate] AS iti_date,
            T3.[CreateDate] AS iti_created_date,
            CASE T0.[U_ARK_DelivStat] WHEN 'N' THEN 'Not Delivered' WHEN 'Y' THEN 'Delivered' END AS delivery_status,
            T0.[U_MIS_DeliveryTime] AS delivery_time,
            T0.U_ARK_SendToSite,
            T0.U_ARK_SendToBpn,
            T0.U_ARK_SendToAcc,
            T0.U_ARK_TANo,
            T0.JrnlMemo
        FROM 
            OWTR T0 
            INNER JOIN WTR1 T1 ON T0.[DocEntry] = T1.[DocEntry]
            INNER JOIN OITW T2 ON T1.[ItemCode] = T2.[ItemCode]
            LEFT JOIN OWTR T3 ON T0.DocNum = T3.U_MIS_DocRefNo
            LEFT JOIN OPDN T4 ON T0.U_MIS_GRPONo = T4.DocNum
            LEFT JOIN PDN1 T5 ON T4.DocEntry = T5.DocEntry
            LEFT JOIN OPOR T6 ON T5.BaseRef = T6.DocNum
            LEFT JOIN OPRQ T7 ON T6.U_MIS_PRNo = T7.DocNum
            LEFT JOIN POR1 T8 ON T6.DocEntry = T8.DocEntry
            LEFT JOIN ORDR T9 ON T8.U_MISMRNo = T9.DocNum
            LEFT JOIN OPDN T10 ON T0.U_MIS_GRPONo = T10.DocNum
            LEFT JOIN OUSR T11 ON T0.UserSign = T11.USERID
        WHERE 
            T0.[CreateDate] >= ? 
            AND T0.[CreateDate] <= ? 
            AND T2.[WhsCode] = T0.[Filler] 
            AND T0.[U_MIS_TransferType] = 'OUT'
        ORDER BY T0.[DocNum] ASC
    ";
    
    $fullResults = DB::connection('sap_sql')->select($fullSql, [$startDateTime, $endDateTime]);
    $fullCount = count($fullResults);
    echo "   Full query results: {$fullCount} records" . PHP_EOL;
    
    if ($fullCount > 0 && $fullCount < 10) {
        echo "   Sample records:" . PHP_EOL;
        foreach (array_slice($fullResults, 0, 3) as $idx => $row) {
            $row = (array) $row;
            echo "     Record " . ($idx + 1) . ":" . PHP_EOL;
            echo "       ITO No: " . ($row['ito_no'] ?? 'NULL') . PHP_EOL;
            echo "       CreateDate: " . ($row['ito_created_date'] ?? 'NULL') . PHP_EOL;
            echo "       DocDate: " . ($row['ito_date'] ?? 'NULL') . PHP_EOL;
        }
    }
    
    // Compare counts
    if ($totalCount != $fullCount) {
        echo "   ⚠ WARNING: Count mismatch!" . PHP_EOL;
        echo "     Count query: {$totalCount}" . PHP_EOL;
        echo "     Full query: {$fullCount}" . PHP_EOL;
    }
    
} catch (\Exception $e) {
    echo "   ✗ Query failed: " . $e->getMessage() . PHP_EOL;
    echo "   Stack trace:" . PHP_EOL;
    echo "   " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL;

// Test 6: Check timezone and date format
echo "6. Date/Time Configuration:" . PHP_EOL;
echo "   PHP Timezone: " . date_default_timezone_get() . PHP_EOL;
echo "   PHP Current Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "   Test Date Start: {$startDateTime}" . PHP_EOL;
echo "   Test Date End: {$endDateTime}" . PHP_EOL;

echo PHP_EOL;

// Test 7: Test using SapService
echo "7. SapService Test:" . PHP_EOL;
try {
    $sapService = app(\App\Services\SapService::class);
    $serviceResults = $sapService->executeItoSqlQuery($testDate, $testDate);
    echo "   ✓ SapService query executed successfully" . PHP_EOL;
    echo "   Records returned: " . count($serviceResults) . PHP_EOL;
} catch (\Exception $e) {
    echo "   ✗ SapService query failed: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;
echo "=== Test Complete ===" . PHP_EOL;

