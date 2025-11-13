<?php

namespace App\Console\Commands;

use App\Services\SapService;
use Illuminate\Console\Command;
use GuzzleHttp\Exception\RequestException;

class TestSapConnection extends Command
{
    protected $signature = 'sap:test-connection';
    protected $description = 'Test SAP B1 Service Layer connection and query endpoints';

    public function handle(SapService $sapService)
    {
        $this->info('Testing SAP B1 Service Layer Connection...');
        $this->newLine();

        // Test 1: Login
        $this->info('1. Testing Login endpoint...');
        try {
            $result = $sapService->login();
            $this->info('   ✓ Login successful!');
        } catch (\Exception $e) {
            $this->error('   ✗ Login failed: ' . $e->getMessage());
            return 1;
        }
        $this->newLine();

        // Test 2: Check Service Document to see available endpoints
        $this->info('2. Checking Service Document for available endpoints...');
        $transferEntities = [];
        try {
            $client = $sapService->getClient();
            $response = $client->get('', ['query' => ['$format' => 'json']]);
            $serviceDoc = json_decode($response->getBody()->getContents(), true);
            $this->info('   ✓ Service Document accessible');
            if (isset($serviceDoc['value'])) {
                $this->info('   Searching for transfer/inventory related entities...');
                foreach ($serviceDoc['value'] as $endpoint) {
                    $name = $endpoint['name'] ?? 'Unknown';
                    $kind = $endpoint['kind'] ?? '';
                    
                    // Look for transfer, inventory, or ITO related entities
                    if (stripos($name, 'transfer') !== false || 
                        stripos($name, 'inventory') !== false ||
                        stripos($name, 'ito') !== false ||
                        stripos($name, 'stock') !== false ||
                        stripos($name, 'warehouse') !== false) {
                        $transferEntities[] = $name;
                        $this->line("     ⭐ {$name} ({$kind})");
                    }
                }
                if (count($transferEntities) > 0) {
                    $this->info('   Found ' . count($transferEntities) . ' transfer/inventory related entities');
                } else {
                    $this->warn('   No transfer/inventory entities found in first scan');
                }
            }
        } catch (RequestException $e) {
            $this->warn('   ⚠ Could not access service document: ' . $e->getMessage());
        }
        $this->newLine();

        // Test 2b: List available SQLQueries
        $this->info('2b. Testing SQLQueries endpoint (list all queries)...');
        try {
            $client = $sapService->getClient();
            $response = $client->get('SQLQueries', [
                'query' => ['$top' => 20, '$select' => 'QueryId,QueryName']
            ]);
            $queries = json_decode($response->getBody()->getContents(), true);
            $this->info('   ✓ SQLQueries endpoint accessible');
            if (isset($queries['value']) && count($queries['value']) > 0) {
                $this->info('   Available SQLQueries:');
                $found = false;
                foreach ($queries['value'] as $query) {
                    $queryId = $query['QueryId'] ?? $query['Code'] ?? 'Unknown';
                    $queryName = $query['QueryName'] ?? '';
                    if (stripos($queryId, 'ito') !== false || stripos($queryName, 'ito') !== false) {
                        $this->line("     ⭐ {$queryId} - {$queryName}");
                        $found = true;
                    } else {
                        $this->line("     - {$queryId} - {$queryName}");
                    }
                }
                if (!$found) {
                    $this->warn("   ⚠ Query '{$queryId}' not found in list");
                }
            }
        } catch (RequestException $e) {
            $this->warn('   ⚠ Could not list SQLQueries: ' . $e->getMessage());
        }
        $this->newLine();

        // Test 3: Try direct OData queries on OWTR (Inventory Transfer Requests)
        $this->info('3. Testing direct OData queries on OWTR entity (based on list_ITO.sql)...');
        $testParams = [
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-10',
        ];
        
        // Based on SQL: FROM OWTR T0 WHERE T0.[CreateDate] >= @A AND T0.[CreateDate] <= @B
        // Common SAP B1 Service Layer entity names for OWTR table
        $entities = array_unique(array_merge(
            $transferEntities, // From service document scan
            [
                'StockTransferRequests',  // Most common name for OWTR
                'InventoryTransferRequests',
                'InventoryTransfers',
                'StockTransfers',
                'WarehouseTransfers',
            ]
        ));
        
        if (empty($entities)) {
            $this->warn('   No entities found. Trying common names...');
            $entities = ['StockTransferRequests', 'InventoryTransferRequests'];
        }
        
        $workingEntity = null;
        $sampleRecord = null;
        
        foreach ($entities as $entity) {
            $this->info("   Testing entity: {$entity}");
            try {
                $client = $sapService->getClient();
                
                // First, try to get a sample record WITHOUT $select to see all available properties
                $this->line("     Step 1: Getting sample record to discover properties...");
                $response = $client->get($entity, [
                    'query' => [
                        '$top' => 1
                    ]
                ]);
                $result = json_decode($response->getBody()->getContents(), true);
                
                if (isset($result['value']) && count($result['value']) > 0) {
                    $this->info("     ✓ Entity '{$entity}' exists and accessible!");
                    $sampleRecord = $result['value'][0];
                    $allFields = array_keys($sampleRecord);
                    $this->info('     Available fields (' . count($allFields) . '): ' . implode(', ', array_slice($allFields, 0, 20)));
                    if (count($allFields) > 20) {
                        $this->line('     ... and ' . (count($allFields) - 20) . ' more fields');
                    }
                    
                    // Find date-related fields
                    $dateFields = array_filter($allFields, function($field) {
                        return stripos($field, 'date') !== false || 
                               stripos($field, 'create') !== false ||
                               stripos($field, 'time') !== false;
                    });
                    if (!empty($dateFields)) {
                        $this->info('     Date-related fields: ' . implode(', ', $dateFields));
                    }
                    
                    // Try to identify the correct date field for filtering
                    $possibleDateFields = ['CreateDate', 'CreationDate', 'DocDate', 'Date', 'CreateTime'];
                    $workingDateField = null;
                    
                    foreach ($possibleDateFields as $dateField) {
                        if (in_array($dateField, $allFields)) {
                            $workingDateField = $dateField;
                            $this->info("     Found date field: {$dateField}");
                            break;
                        }
                    }
                    
                    if (!$workingDateField && !empty($dateFields)) {
                        $workingDateField = reset($dateFields);
                        $this->info("     Using first date field found: {$workingDateField}");
                    }
                    
                    if ($workingDateField) {
                        // Now try with date filter using the correct field name
                        $this->line("     Step 2: Testing with date filter using '{$workingDateField}'...");
                        $response = $client->get($entity, [
                            'query' => [
                                '$top' => 5,
                                '$filter' => "{$workingDateField} ge datetime'{$testParams['start_date']}T00:00:00' and {$workingDateField} le datetime'{$testParams['end_date']}T23:59:59'"
                            ]
                        ]);
                        $filteredResult = json_decode($response->getBody()->getContents(), true);
                        
                        if (isset($filteredResult['value'])) {
                            $count = count($filteredResult['value']);
                            $this->info("     ✓ Filtered query successful! Found {$count} records");
                            if ($count > 0) {
                                $firstRecord = $filteredResult['value'][0];
                                $this->line('     First record keys: ' . implode(', ', array_slice(array_keys($firstRecord), 0, 10)));
                            }
                            $workingEntity = $entity;
                            $this->newLine();
                            $this->info("   ⭐ FOUND WORKING ENTITY: {$entity}");
                            $this->line("   Date field: {$workingDateField}");
                            $this->line("   This entity maps to OWTR table and can be used to query ITO data directly");
                            break;
                        }
                    } else {
                        $this->warn("     ⚠ Could not identify date field for filtering");
                    }
                } else {
                    $this->line("     - Entity exists but no records found");
                }
            } catch (RequestException $e) {
                $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'N/A';
                if ($statusCode == 404) {
                    $this->line("     - Entity not found (404)");
                } else {
                    $errorBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
                    $this->warn("     - Error (HTTP {$statusCode})");
                    if ($errorBody) {
                        $errorData = json_decode($errorBody, true);
                        if (isset($errorData['error']['message']['value'])) {
                            $this->line('       ' . $errorData['error']['message']['value']);
                        }
                    }
                }
            }
        }
        
        if (!$workingEntity) {
            $this->warn('   ⚠ Could not find a working entity for OWTR table');
            $this->line('   Will continue testing query execution methods...');
        }
        $this->newLine();

        // Test 4: Try different query execution formats (if user query is still needed)
        $this->info('4. Testing query execution with different endpoint formats...');
        $queryId = config('sap.query_ids.list_ito', 'list_ito');

        $formats = [
            // Try SQLQueries endpoint with positional parameters (since SQL uses [%0] and [%1])
            ['method' => 'GET', 'endpoint' => "SQLQueries('{$queryId}')", 'params' => ['0' => $testParams['start_date'], '1' => $testParams['end_date']], 'type' => 'query'],
            ['method' => 'GET', 'endpoint' => "SQLQueries('{$queryId}')", 'params' => ['%0' => $testParams['start_date'], '%1' => $testParams['end_date']], 'type' => 'query'],
            ['method' => 'GET', 'endpoint' => "SQLQueries('{$queryId}')", 'params' => ['A' => $testParams['start_date'], 'B' => $testParams['end_date']], 'type' => 'query'],
            ['method' => 'POST', 'endpoint' => "SQLQueries('{$queryId}')/List", 'params' => ['0' => $testParams['start_date'], '1' => $testParams['end_date']], 'type' => 'json'],
            ['method' => 'POST', 'endpoint' => "SQLQueries('{$queryId}')/List", 'params' => ['Parameters' => [$testParams['start_date'], $testParams['end_date']]], 'type' => 'json'],
            ['method' => 'POST', 'endpoint' => "SQLQueries('{$queryId}')/List", 'params' => ['QueryParams' => ['0' => $testParams['start_date'], '1' => $testParams['end_date']]], 'type' => 'json'],
            
            // Try UserQueries endpoint with positional parameters
            ['method' => 'GET', 'endpoint' => "UserQueries('{$queryId}')", 'params' => ['0' => $testParams['start_date'], '1' => $testParams['end_date']], 'type' => 'query'],
            ['method' => 'POST', 'endpoint' => "UserQueries('{$queryId}')/Execute", 'params' => ['0' => $testParams['start_date'], '1' => $testParams['end_date']], 'type' => 'json'],
            ['method' => 'POST', 'endpoint' => "UserQueries('{$queryId}')/Execute", 'params' => ['Parameters' => [$testParams['start_date'], $testParams['end_date']]], 'type' => 'json'],
            ['method' => 'POST', 'endpoint' => "UserQueries('{$queryId}')/Execute", 'params' => ['QueryParams' => ['0' => $testParams['start_date'], '1' => $testParams['end_date']]], 'type' => 'json'],
            
            // Try with different parameter formats
            ['method' => 'GET', 'endpoint' => "Queries('{$queryId}')", 'params' => ['@A' => $testParams['start_date'], '@B' => $testParams['end_date']], 'type' => 'query'],
            ['method' => 'GET', 'endpoint' => "Queries('{$queryId}')", 'params' => ['#@A' => $testParams['start_date'], '#@B' => $testParams['end_date']], 'type' => 'query'],
            ['method' => 'POST', 'endpoint' => "Queries('{$queryId}')/Execute", 'params' => ['@A' => $testParams['start_date'], '@B' => $testParams['end_date']], 'type' => 'json'],
            
            // Try QueryService with different formats
            ['method' => 'POST', 'endpoint' => 'QueryService_ExecuteQuery', 'params' => [
                'QueryPath' => $queryId,
                'QueryOption' => "#@A={$testParams['start_date']};#@B={$testParams['end_date']}"
            ], 'type' => 'json'],
            ['method' => 'POST', 'endpoint' => 'QueryService_ExecuteQuery', 'params' => [
                'QueryPath' => $queryId,
                'QueryOption' => "@A={$testParams['start_date']};@B={$testParams['end_date']}"
            ], 'type' => 'json'],
        ];

        foreach ($formats as $format) {
            $method = $format['method'];
            $endpoint = $format['endpoint'];
            $params = $format['params'];
            $type = $format['type'];
            
            $this->info("   Testing: {$method} {$endpoint}");
            try {
                $client = $sapService->getClient();
                if ($method === 'POST') {
                    if ($type === 'json') {
                        $response = $client->post($endpoint, ['json' => $params]);
                    } else {
                        $response = $client->post($endpoint, ['query' => $params]);
                    }
                } else {
                    $response = $client->get($endpoint, ['query' => $params]);
                }
                
                $result = json_decode($response->getBody()->getContents(), true);
                $this->info("   ✓ SUCCESS with endpoint: {$method} {$endpoint}");
                $this->line('   Response keys: ' . implode(', ', array_keys($result)));
                if (isset($result['value'])) {
                    $this->info('   Records found: ' . count($result['value']));
                    if (count($result['value']) > 0) {
                        $this->line('   First record keys: ' . implode(', ', array_keys($result['value'][0])));
                    }
                }
                $this->newLine();
                $this->info("   ✓ WORKING FORMAT FOUND! Use this in SapService:");
                $this->line("   Method: {$method}");
                $this->line("   Endpoint: {$endpoint}");
                $this->line("   Params: " . json_encode($params));
                return 0; // Stop on first success
            } catch (RequestException $e) {
                $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'N/A';
                $this->warn("   ✗ Failed (HTTP {$statusCode})");
                if ($e->getResponse()) {
                    $errorBody = $e->getResponse()->getBody()->getContents();
                    $errorData = json_decode($errorBody, true);
                    if (isset($errorData['error']['message']['value'])) {
                        $this->line('   Error: ' . $errorData['error']['message']['value']);
                    } else {
                        $this->line('   Error: ' . substr($errorBody, 0, 150));
                    }
                }
            }
        }

        $this->newLine();
        $this->info('Test completed!');
        return 0;
    }
}
