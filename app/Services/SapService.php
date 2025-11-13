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
            // Prioritize DocDate since that's what the SQL query uses (DocDate in OWTR table)
            $dateFields = ['DocDate', 'CreationDate', 'CreateDate'];
            $workingDateField = null;
            
            // First, try to discover the correct date field by getting a sample record
            foreach ($dateFields as $dateField) {
                try {
                    $testResponse = $this->client->get($entityName, [
                        'query' => [
                            '$top' => 1,
                            '$select' => $dateField
                        ]
                    ]);
                    $testResult = json_decode($testResponse->getBody()->getContents(), true);
                    if (isset($testResult['value']) && count($testResult['value']) > 0) {
                        $workingDateField = $dateField;
                        Log::channel('sap')->info("Using date field: {$dateField} for entity: {$entityName}");
                        break;
                    }
                } catch (\Exception $e) {
                    // Try next field
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
            // First, check if the field exists by getting a sample record
            // Note: We'll make this optional - if it filters out all records, we can remove it
            try {
                $testResponse = $this->client->get($entityName, ['query' => ['$top' => 1]]);
                $testResult = json_decode($testResponse->getBody()->getContents(), true);
                if (isset($testResult['value'][0]) && array_key_exists('U_MIS_TransferType', $testResult['value'][0])) {
                    // Test if adding this filter would return any results
                    $testFilterWithType = $filter . " and U_MIS_TransferType eq 'OUT'";
                    try {
                        $testResponse2 = $this->client->get($entityName, [
                            'query' => [
                                '$filter' => $testFilterWithType,
                                '$top' => 1
                            ]
                        ]);
                        $testResult2 = json_decode($testResponse2->getBody()->getContents(), true);
                        if (isset($testResult2['value']) && count($testResult2['value']) > 0) {
                            $filter .= " and U_MIS_TransferType eq 'OUT'";
                            Log::channel('sap')->info("Added U_MIS_TransferType filter: OUT (verified it returns results)");
                        } else {
                            Log::channel('sap')->warning("U_MIS_TransferType='OUT' filter would return 0 records, skipping it");
                        }
                    } catch (\Exception $e) {
                        // If test fails, don't add the filter
                        Log::channel('sap')->warning("Could not verify U_MIS_TransferType filter, skipping it: " . $e->getMessage());
                    }
                } else {
                    Log::channel('sap')->info("U_MIS_TransferType field not found in entity, filtering without it");
                }
            } catch (\Exception $e) {
                // Field might not exist or be accessible, skip it
                Log::channel('sap')->warning("Could not check U_MIS_TransferType field, filtering without it: " . $e->getMessage());
            }
            
            Log::channel('sap')->info("Using filter: {$filter}");
            
            try {
                $response = $this->client->get($entityName, [
                    'query' => [
                        '$filter' => $filter,
                        '$orderby' => 'DocNum asc',
                        '$top' => 1000, // Adjust based on volume
                    ]
                ]);
                $result = json_decode($response->getBody()->getContents(), true);
                return $result['value'] ?? [];
            } catch (RequestException $e) {
                // If 401, re-login and retry once
                if ($e->getResponse() && $e->getResponse()->getStatusCode() === 401) {
                    Log::channel('sap')->warning("Session expired during query, re-logging in and retrying...");
                    $this->login();
                    $response = $this->client->get($entityName, [
                        'query' => [
                            '$filter' => $filter,
                            '$orderby' => 'DocNum asc',
                            '$top' => 1000,
                        ]
                    ]);
                    $result = json_decode($response->getBody()->getContents(), true);
                    return $result['value'] ?? [];
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

    // Add more methods later (e.g., for invoice creation)
}
