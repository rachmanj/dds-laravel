<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class SapService
{
    protected $client;
    protected $config;
    protected $cookieJar;

    public function __construct()
    {
        $this->config = config('sap');
        $this->cookieJar = new CookieJar();
        // Ensure base_uri ends with /v1/ for SAP Service Layer
        $baseUri = rtrim($this->config['server_url'], '/');
        if (!str_ends_with($baseUri, '/v1')) {
            $baseUri .= '/v1';
        }
        $baseUri .= '/';
        $this->client = new Client([
            'base_uri' => $baseUri,
            'cookies' => $this->cookieJar,
            'headers' => ['Content-Type' => 'application/json'],
            'verify' => false, // Disable for dev; enable in prod
        ]);
    }

    public function getClient()
    {
        return $this->client;
    }

    public function login()
    {
        try {
            $response = $this->client->post('Login', [
                'json' => [
                    'CompanyDB' => $this->config['db_name'],
                    'UserName' => $this->config['user'],
                    'Password' => $this->config['password'],
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return true; // Session set in cookies
            }

            throw new \Exception('Login failed');
        } catch (RequestException $e) {
            Log::channel('sap')->error('SAP Login failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function executeQuery(string $queryId, array $params)
    {
        if (!$this->cookieJar->count()) {
            $this->login();
        }

        try {
            // SAP B1 Service Layer uses GET /Queries('query_name') with parameters in query string
            $queryParams = [
                '$format' => 'json',
                '@A' => $params['start_date'],
                '@B' => $params['end_date'],
            ];

            $response = $this->client->get("Queries('{$queryId}')", [
                'query' => $queryParams,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            // SAP returns query results in different formats, handle accordingly
            if (isset($result['value'])) {
                return $result['value'];
            }

            return $result;
        } catch (RequestException $e) {
            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 401) {
                $this->login(); // Retry once with fresh login
                return $this->executeQuery($queryId, $params);
            }

            Log::channel('sap')->error('SAP Query failed: ' . $e->getMessage());
            if ($e->getResponse()) {
                $errorBody = $e->getResponse()->getBody()->getContents();
                Log::channel('sap')->error('SAP Error Response: ' . $errorBody);
            }
            throw $e;
        }
    }

    public function getBusinessPartner($cardCode)
    {
        if (!$this->cookieJar->count()) {
            $this->login();
        }

        try {
            $response = $this->client->get("BusinessPartners('$cardCode')", [
                'query' => ['$select' => 'CardCode,CardName,CardType']
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::channel('sap')->error('SAP BusinessPartner query failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createApInvoice(array $payload)
    {
        if (!$this->cookieJar->count()) {
            $this->login();
        }

        try {
            $response = $this->client->post('Invoices', [
                'json' => $payload
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::channel('sap')->error('SAP Invoice creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getRecentInvoices($fromDate)
    {
        if (!$this->cookieJar->count()) {
            $this->login();
        }

        try {
            $response = $this->client->get('Invoices', [
                'query' => [
                    '$filter' => "DocDate ge '$fromDate'",
                    '$select' => 'DocEntry,DocNum,Comments',
                    '$top' => 1000, // Adjust based on volume
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true)['value'];
        } catch (RequestException $e) {
            Log::channel('sap')->error('SAP recent invoices query failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Query Stock Transfer Requests (OWTR table) directly using OData
     * This is an alternative to executing user queries
     * 
     * @param string $startDate Y-m-d format
     * @param string $endDate Y-m-d format
     * @param string|null $entityName Entity name (null = auto-detect)
     * @return array
     */
    public function getStockTransferRequests($startDate, $endDate, $entityName = null)
    {
        if (!$this->cookieJar->count()) {
            $this->login();
        }

        try {
            // Auto-detect entity if not provided - prioritize actual transfers over drafts
            if (!$entityName) {
                $entitiesToTry = ['InventoryTransferRequests', 'StockTransfers', 'StockTransferDrafts'];
                foreach ($entitiesToTry as $entity) {
                    try {
                        // Ensure we have a valid session
                        if (!$this->cookieJar->count()) {
                            $this->login();
                        }

                        $testResponse = $this->client->get($entity, ['query' => ['$top' => 1]]);
                        $testResult = json_decode($testResponse->getBody()->getContents(), true);
                        if (isset($testResult['value']) && count($testResult['value']) > 0) {
                            $entityName = $entity;
                            Log::channel('sap')->info("Auto-selected entity: {$entityName}");
                            break;
                        }
                    } catch (\GuzzleHttp\Exception\ClientException $e) {
                        // If 401, try to login and retry once
                        if ($e->getResponse() && $e->getResponse()->getStatusCode() === 401) {
                            try {
                                $this->login();
                                $testResponse = $this->client->get($entity, ['query' => ['$top' => 1]]);
                                $testResult = json_decode($testResponse->getBody()->getContents(), true);
                                if (isset($testResult['value']) && count($testResult['value']) > 0) {
                                    $entityName = $entity;
                                    Log::channel('sap')->info("Auto-selected entity: {$entityName} (after re-login)");
                                    break;
                                }
                            } catch (\Exception $e2) {
                                continue;
                            }
                        }
                        continue;
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                if (!$entityName) {
                    throw new \Exception("Could not find a working entity. Tried: " . implode(', ', $entitiesToTry));
                }
            }

            // Format dates for OData datetime filter
            $startDateTime = $startDate . 'T00:00:00';
            $endDateTime = $endDate . 'T23:59:59';

            // Try different date field names (SAP B1 Service Layer uses different names)
            // Prioritize CreateDate to match the SQL query WHERE clause: T0.[CreateDate] >= @A AND T0.[CreateDate] <= @B
            $dateFields = ['CreateDate', 'CreationDate', 'DocDate'];
            $workingDateField = null;

            // First, try to discover the correct date field by getting a sample record
            // Test each field by trying to use it in a filter (more reliable than $select)
            foreach ($dateFields as $dateField) {
                try {
                    // Test if the field exists and can be used in a filter
                    // Use a wide date range to ensure we get results
                    $testFilter = "{$dateField} ge datetime'2000-01-01T00:00:00'";
                    $testResponse = $this->client->get($entityName, [
                        'query' => [
                            '$filter' => $testFilter,
                            '$top' => 1
                        ]
                    ]);
                    $testResult = json_decode($testResponse->getBody()->getContents(), true);
                    if (isset($testResult['value']) && count($testResult['value']) > 0) {
                        $workingDateField = $dateField;
                        Log::channel('sap')->info("Using date field: {$dateField} for entity: {$entityName} (verified with filter)");
                        break;
                    }
                } catch (\Exception $e) {
                    // Try next field
                    Log::channel('sap')->debug("Date field {$dateField} test failed: " . $e->getMessage());
                    continue;
                }
            }

            if (!$workingDateField) {
                // Fallback: try without $select to see all fields, then use first date-like field
                $testResponse = $this->client->get($entityName, ['query' => ['$top' => 1]]);
                $testResult = json_decode($testResponse->getBody()->getContents(), true);
                if (isset($testResult['value'][0])) {
                    $allFields = array_keys($testResult['value'][0]);
                    foreach ($allFields as $field) {
                        if (stripos($field, 'date') !== false || stripos($field, 'create') !== false) {
                            $workingDateField = $field;
                            Log::channel('sap')->info("Auto-discovered date field: {$field} for entity: {$entityName}");
                            break;
                        }
                    }
                }
            }

            if (!$workingDateField) {
                throw new \Exception("Could not determine date field for entity: {$entityName}");
            }

            // Based on SQL: WHERE T0.[CreateDate] >= @A AND T0.[CreateDate] <= @B 
            // AND T0.[U_MIS_TransferType] = 'OUT'
            $filter = "{$workingDateField} ge datetime'{$startDateTime}' and {$workingDateField} le datetime'{$endDateTime}'";

            // Try to add TransferType filter if the field exists
            // Query 5 shows 202 records with CreateDate + TransferType='OUT', so we should try to apply it
            // UDFs like U_MIS_TransferType are accessed via DynamicProperties in SAP B1 Service Layer
            // First, check if DynamicProperties is available and contains U_MIS_TransferType
            try {
                // Try to get sample records with DynamicProperties to access UDFs
                $sampleResponse = $this->client->get($entityName, [
                    'query' => [
                        '$filter' => $filter,
                        '$top' => 20,
                        '$select' => 'DocNum,DynamicProperties'
                    ]
                ]);
                $sampleResult = json_decode($sampleResponse->getBody()->getContents(), true);

                // Also try direct field access (in case it's exposed directly)
                $sampleResponseDirect = null;
                try {
                    $sampleResponseDirect = $this->client->get($entityName, [
                        'query' => [
                            '$filter' => $filter,
                            '$top' => 20,
                            '$select' => 'DocNum,U_MIS_TransferType'
                        ]
                    ]);
                } catch (\Exception $e) {
                    // Direct access might not work, that's okay
                }

                // Merge results if both queries worked
                if ($sampleResponseDirect) {
                    $sampleResultDirect = json_decode($sampleResponseDirect->getBody()->getContents(), true);
                    // Prefer direct access if available
                    if (isset($sampleResultDirect['value']) && count($sampleResultDirect['value']) > 0) {
                        $sampleResult = $sampleResultDirect;
                    }
                }

                if (isset($sampleResult['value']) && count($sampleResult['value']) > 0) {
                    $transferTypes = [];
                    foreach ($sampleResult['value'] as $rec) {
                        // Try direct field access first
                        $type = $rec['U_MIS_TransferType'] ?? null;

                        // If not found, try DynamicProperties
                        if ($type === null && isset($rec['DynamicProperties'])) {
                            $type = $rec['DynamicProperties']['U_MIS_TransferType'] ?? null;
                        }

                        // If still null, check if DynamicProperties is an object/array
                        if ($type === null && isset($rec['DynamicProperties']) && is_array($rec['DynamicProperties'])) {
                            foreach ($rec['DynamicProperties'] as $key => $value) {
                                if (strtoupper($key) === 'U_MIS_TRANSFERTYPE') {
                                    $type = $value;
                                    break;
                                }
                            }
                        }

                        $type = $type ?? 'NULL';
                        if (!isset($transferTypes[$type])) {
                            $transferTypes[$type] = 0;
                        }
                        $transferTypes[$type]++;
                    }
                    Log::channel('sap')->info("U_MIS_TransferType values found in sample: " . json_encode($transferTypes));

                    // Check if 'OUT' value exists (case-insensitive)
                    $hasOut = false;
                    foreach (array_keys($transferTypes) as $type) {
                        if (strtoupper($type) === 'OUT') {
                            $hasOut = true;
                            break;
                        }
                    }

                    if ($hasOut) {
                        // Test if adding this filter would return any results
                        // Try both direct field access and DynamicProperties syntax
                        $filterVariations = [
                            "U_MIS_TransferType eq 'OUT'",
                            "DynamicProperties/U_MIS_TransferType eq 'OUT'",
                        ];

                        $filterApplied = false;
                        foreach ($filterVariations as $filterVar) {
                            $testFilterWithType = $filter . " and " . $filterVar;
                            try {
                                $testResponse2 = $this->client->get($entityName, [
                                    'query' => [
                                        '$filter' => $testFilterWithType,
                                        '$top' => 10,
                                        '$count' => 'true'
                                    ]
                                ]);
                                $testResult2 = json_decode($testResponse2->getBody()->getContents(), true);
                                $count = isset($testResult2['value']) ? count($testResult2['value']) : 0;
                                $totalCount = $testResult2['@odata.count'] ?? $count;

                                if ($count > 0 || $totalCount > 0) {
                                    $filter .= " and " . $filterVar;
                                    Log::channel('sap')->info("Added U_MIS_TransferType filter: OUT (using '{$filterVar}', found {$count} records, total: {$totalCount})");
                                    $filterApplied = true;
                                    break;
                                }
                            } catch (\Exception $e) {
                                Log::channel('sap')->debug("Filter variation '{$filterVar}' failed: " . $e->getMessage());
                                continue;
                            }
                        }

                        if (!$filterApplied) {
                            Log::channel('sap')->warning("U_MIS_TransferType='OUT' filter returned 0 records even though 'OUT' values exist in sample. Tried both direct and DynamicProperties syntax.");
                        }
                    } else {
                        Log::channel('sap')->warning("No 'OUT' values found in U_MIS_TransferType field. Values: " . implode(', ', array_keys($transferTypes)));
                        Log::channel('sap')->warning("NOTE: OData entity may not expose U_MIS_TransferType field properly. SQL Query 5 shows 202 records with this filter, but OData cannot replicate it. Consider using SQL query execution method if available.");
                    }
                } else {
                    // Field might not exist, check if it's in the entity at all
                    $testResponse = $this->client->get($entityName, ['query' => ['$top' => 1]]);
                    $testResult = json_decode($testResponse->getBody()->getContents(), true);
                    if (isset($testResult['value'][0]) && !array_key_exists('U_MIS_TransferType', $testResult['value'][0])) {
                        Log::channel('sap')->info("U_MIS_TransferType field not found in entity, filtering without it");
                    }
                }
            } catch (\Exception $e) {
                // Field might not exist or be accessible, skip it
                Log::channel('sap')->warning("Could not check U_MIS_TransferType field, filtering without it: " . $e->getMessage());
            }

            Log::channel('sap')->info("Using filter: {$filter}");

            // Handle pagination to get all records (SAP B1 Service Layer may limit results)
            $allResults = [];
            $skip = 0;
            $top = 1000; // Fetch in batches
            $hasMore = true;

            try {
                while ($hasMore) {
                    $response = $this->client->get($entityName, [
                        'query' => [
                            '$filter' => $filter,
                            '$orderby' => 'DocNum asc',
                            '$top' => $top,
                            '$skip' => $skip,
                        ]
                    ]);
                    $result = json_decode($response->getBody()->getContents(), true);
                    $batch = $result['value'] ?? [];

                    if (empty($batch)) {
                        $hasMore = false;
                    } else {
                        $allResults = array_merge($allResults, $batch);
                        Log::channel('sap')->info("Fetched batch: " . count($batch) . " records (total so far: " . count($allResults) . ")");

                        // If we got fewer records than requested, we've reached the end
                        if (count($batch) < $top) {
                            $hasMore = false;
                        } else {
                            $skip += $top;
                        }
                    }
                }

                // Deduplicate by DocNum (since SQL uses SELECT DISTINCT)
                $uniqueResults = [];
                $seenDocNums = [];
                foreach ($allResults as $record) {
                    $docNum = $record['DocNum'] ?? $record['Reference1'] ?? null;
                    if ($docNum && !in_array($docNum, $seenDocNums)) {
                        $seenDocNums[] = $docNum;
                        $uniqueResults[] = $record;
                    }
                }

                Log::channel('sap')->info("Total records fetched: " . count($allResults) . ", Unique DocNums: " . count($uniqueResults));
                return $uniqueResults;
            } catch (RequestException $e) {
                // If 401, re-login and retry once
                if ($e->getResponse() && $e->getResponse()->getStatusCode() === 401) {
                    Log::channel('sap')->warning("Session expired during query, re-logging in and retrying...");
                    $this->login();
                    // Retry the pagination loop
                    $allResults = [];
                    $skip = 0;
                    $hasMore = true;
                    while ($hasMore) {
                        $response = $this->client->get($entityName, [
                            'query' => [
                                '$filter' => $filter,
                                '$orderby' => 'DocNum asc',
                                '$top' => $top,
                                '$skip' => $skip,
                            ]
                        ]);
                        $result = json_decode($response->getBody()->getContents(), true);
                        $batch = $result['value'] ?? [];

                        if (empty($batch)) {
                            $hasMore = false;
                        } else {
                            $allResults = array_merge($allResults, $batch);
                            if (count($batch) < $top) {
                                $hasMore = false;
                            } else {
                                $skip += $top;
                            }
                        }
                    }

                    // Deduplicate
                    $uniqueResults = [];
                    $seenDocNums = [];
                    foreach ($allResults as $record) {
                        $docNum = $record['DocNum'] ?? $record['Reference1'] ?? null;
                        if ($docNum && !in_array($docNum, $seenDocNums)) {
                            $seenDocNums[] = $docNum;
                            $uniqueResults[] = $record;
                        }
                    }
                    return $uniqueResults;
                }
                throw $e;
            }
        } catch (RequestException $e) {
            Log::channel('sap')->error('SAP StockTransferRequests query failed: ' . $e->getMessage());
            if ($e->getResponse()) {
                $errorBody = $e->getResponse()->getBody()->getContents();
                Log::channel('sap')->error('SAP Error Response: ' . $errorBody);
            }
            throw $e;
        }
    }

    /**
     * Execute SQL query directly on SAP B1 SQL Server
     * This method executes the exact SQL query from list_ITO.sql
     * 
     * @param string $startDate Y-m-d format
     * @param string $endDate Y-m-d format
     * @return array
     */
    public function executeItoSqlQuery($startDate, $endDate)
    {
        try {
            // Use Laravel's DB facade with the SAP SQL connection
            $connection = \Illuminate\Support\Facades\DB::connection('sap_sql');

            // Convert dates to SQL Server datetime format
            $startDateTime = $startDate . ' 00:00:00';
            $endDateTime = $endDate . ' 23:59:59';

            // Execute the SQL query from list_ITO.sql
            // Note: We're using the exact query structure but with parameterized queries for safety
            $sql = "
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

            $results = $connection->select($sql, [$startDateTime, $endDateTime]);

            // Convert stdClass objects to arrays
            $resultsArray = array_map(function ($row) {
                return (array) $row;
            }, $results);

            Log::channel('sap')->info("SQL query executed successfully, found " . count($resultsArray) . " records");

            return $resultsArray;
        } catch (\Exception $e) {
            Log::channel('sap')->error('SAP SQL query failed: ' . $e->getMessage());
            throw $e;
        }
    }

    // Add more methods later (e.g., for invoice creation)
}
