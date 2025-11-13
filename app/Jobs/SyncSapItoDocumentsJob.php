<?php

namespace App\Jobs;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use App\Services\SapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSapItoDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function handle(SapService $sapService)
    {
        try {
            $results = [];
            $method = 'unknown';
            
            // Try SQL Server direct query first (most accurate, matches SQL Query 5)
            try {
                Log::channel('sap')->info('Attempting direct SQL Server query for ITO data...');
                $results = $sapService->executeItoSqlQuery($this->startDate, $this->endDate);
                $method = 'sql_server_direct';
                Log::channel('sap')->info("SQL Server direct query successful, found " . count($results) . " records");
            } catch (\Exception $e) {
                Log::channel('sap')->warning('SQL Server direct query failed, falling back to OData: ' . $e->getMessage());
                
                // Fallback to OData entity query
                try {
                    Log::channel('sap')->info('Attempting direct entity query for ITO data...');
                    $entityResults = $sapService->getStockTransferRequests($this->startDate, $this->endDate);
                    
                    // Transform entity results to match expected format
                    $results = $this->transformEntityResults($entityResults);
                    $method = 'direct_entity_query';
                    Log::channel('sap')->info("Direct entity query successful, found " . count($results) . " records");
                } catch (\Exception $e2) {
                    Log::channel('sap')->warning('Direct entity query failed, falling back to query execution: ' . $e2->getMessage());
                    
                    // Fallback to query execution method
                    try {
                        $results = $sapService->executeQuery(config('sap.query_ids.list_ito'), [
                            'start_date' => $this->startDate,
                            'end_date' => $this->endDate,
                        ]);
                        $method = 'query_execution';
                        Log::channel('sap')->info("Query execution successful, found " . count($results) . " records");
                    } catch (\Exception $e3) {
                        Log::channel('sap')->error('All methods failed. SQL: ' . $e->getMessage() . ' | OData: ' . $e2->getMessage() . ' | Query execution: ' . $e3->getMessage());
                        throw new \Exception('Failed to fetch ITO data: SQL error - ' . $e->getMessage() . ' | OData error - ' . $e2->getMessage() . ' | Query execution error - ' . $e3->getMessage());
                    }
                }
            }

            $itoTypeId = $this->getItoTypeId();
            $batchNo = $this->getBatchNo();
            $successCount = 0;
            $skippedCount = 0;

            DB::beginTransaction();

            foreach ($results as $row) {
                // Handle different formats: SQL query, OData entity query, and query execution
                $itoNo = $row['ito_no'] ?? $row['DocNum'] ?? null;
                if (!$itoNo) {
                    Log::channel('sap')->warning('Skipping row without ito_no/DocNum: ' . json_encode($row));
                    continue;
                }
                
                if (AdditionalDocument::where('document_number', $itoNo)->exists()) {
                    $skippedCount++;
                    continue;
                }

                // SQL query returns fields with exact names from SQL (ito_no, ito_date, etc.)
                // OData returns different field names (DocNum, DocDate, etc.)
                $document = new AdditionalDocument([
                    'type_id' => $itoTypeId,
                    'document_number' => $itoNo,
                    'document_date' => $this->convertDate($row['ito_date'] ?? $row['DocDate'] ?? null),
                    'po_no' => $row['po_no'] ?? null,
                    'receive_date' => $this->convertDate($row['ito_date'] ?? $row['DocDate'] ?? null),
                    'created_by' => Auth::id(),
                    'remarks' => $row['ito_remarks'] ?? $row['Comments'] ?? null,
                    'status' => 'open',
                    'cur_loc' => '000HLOG',
                    'ito_creator' => $row['U_NAME'] ?? $row['ito_created_by'] ?? null,
                    'grpo_no' => $row['grpo_no'] ?? $row['U_MIS_GRPONo'] ?? null,
                    'origin_wh' => $row['origin_whs'] ?? $row['Filler'] ?? $row['FromWarehouse'] ?? null,
                    'destination_wh' => $row['destination_whs'] ?? $row['U_MIS_ToWarehouse'] ?? $row['ToWarehouse'] ?? null,
                    'batch_no' => $batchNo,
                ]);
                $document->save();
                $successCount++;
            }

            DB::commit();

            DB::table('sap_logs')->insert([
                'action' => 'query_sync',
                'status' => 'success',
                'request_payload' => json_encode([
                    'start_date' => $this->startDate, 
                    'end_date' => $this->endDate,
                    'method' => $method
                ]),
                'response_payload' => json_encode(['success' => $successCount, 'skipped' => $skippedCount]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('sap')->error('SAP Sync failed: ' . $e->getMessage());

            DB::table('sap_logs')->insert([
                'action' => 'query_sync',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Transform entity query results to match expected format from SQL query
     * Maps SAP B1 Service Layer entity fields to expected field names
     */
    private function transformEntityResults(array $entityResults): array
    {
        $transformed = [];
        
        foreach ($entityResults as $record) {
            // Helper function to get field value (checks both direct and DynamicProperties)
            $getField = function($fieldName) use ($record) {
                // Try direct access first
                if (isset($record[$fieldName])) {
                    return $record[$fieldName];
                }
                
                // Try DynamicProperties
                if (isset($record['DynamicProperties'])) {
                    if (is_array($record['DynamicProperties']) && isset($record['DynamicProperties'][$fieldName])) {
                        return $record['DynamicProperties'][$fieldName];
                    }
                    // Case-insensitive search in DynamicProperties
                    foreach ($record['DynamicProperties'] as $key => $value) {
                        if (strtoupper($key) === strtoupper($fieldName)) {
                            return $value;
                        }
                    }
                }
                
                return null;
            };
            
            // DocNum might be in DocNum field or Reference1 field
            $docNum = $record['DocNum'] ?? $record['Reference1'] ?? null;
            
            // FromWarehouse maps to origin_whs (Filler in SQL)
            // ToWarehouse maps to destination_whs (U_MIS_ToWarehouse in SQL)
            $originWh = $record['FromWarehouse'] ?? $record['Filler'] ?? null;
            $destWh = $record['ToWarehouse'] ?? $getField('U_MIS_ToWarehouse') ?? null;
            
            $transformed[] = [
                'ito_no' => $docNum,
                'ito_date' => $record['DocDate'] ?? $record['CreationDate'] ?? null,
                'ito_created_date' => $record['CreationDate'] ?? $record['CreateDate'] ?? null,
                'ito_created_by' => $record['UserSign'] ?? null,
                'U_NAME' => null, // Will need to be fetched separately if needed
                'grpo_no' => $getField('U_MIS_GRPONo') ?? null,
                'po_no' => null, // Not directly available, would need to query related entities
                'origin_whs' => $originWh,
                'destination_whs' => $destWh,
                'ito_remarks' => $record['Comments'] ?? $record['JrnlMemo'] ?? null,
                // Additional fields from SQL query
                'iti_no' => null, // Would need to query related OWTR records
                'delivery_status' => $getField('U_ARK_DelivStat') ?? null,
                'delivery_time' => $getField('U_MIS_DeliveryTime') ?? null,
            ];
        }
        
        return $transformed;
    }

    private function getItoTypeId()
    {
        $itoType = AdditionalDocumentType::whereIn('type_name', ['ITO', 'ito'])->first();

        if (!$itoType) {
            throw new \Exception('ITO type not found');
        }

        return $itoType->id;
    }

    private function getBatchNo()
    {
        $current = AdditionalDocument::max('batch_no');

        return $current ? $current + 1 : 1;
    }

    private function convertDate($date)
    {
        if ($date) {
            $timestamp = strtotime($date);

            if ($timestamp) {
                return date('Y-m-d', $timestamp);
            }
        }

        return null;
    }
}
