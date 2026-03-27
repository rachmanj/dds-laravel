<?php

namespace App\Services;

use App\Models\SapDepartment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SapDepartmentSyncService
{
    protected SapService $sapService;

    public function __construct(SapService $sapService)
    {
        $this->sapService = $sapService;
    }

    public function syncDepartments(): array
    {
        try {
            $this->sapService->ensureSession();

            // Call SAP B1 ProfitCenters with $select filter
            $response = $this->sapService->get('ProfitCenters', [
                'query' => [
                    '$select' => 'CenterCode,CenterName',
                ],
            ]);

            if (! isset($response['value']) && ! is_array($response)) {
                throw new \Exception('Invalid response format from SAP ProfitCenters');
            }

            $departments = $response['value'] ?? (is_array($response) ? $response : []);

            $stats = [
                'total' => count($departments),
                'created' => 0,
                'updated' => 0,
                'errors' => 0,
                'error_messages' => [],
            ];

            DB::beginTransaction();

            try {
                foreach ($departments as $sapDepartment) {
                    try {
                        $centerCode = $sapDepartment['CenterCode'] ?? null;
                        $centerName = $sapDepartment['CenterName'] ?? null;

                        if (! $centerCode || ! $centerName) {
                            $stats['errors']++;
                            $stats['error_messages'][] = 'Missing CenterCode or CenterName: '.json_encode($sapDepartment);

                            continue;
                        }

                        // Upsert by sap_code
                        $department = SapDepartment::where('sap_code', $centerCode)->first();

                        if ($department) {
                            $department->update([
                                'name' => $centerName,
                                'is_active' => true,
                                'synced_at' => now(),
                            ]);
                            $stats['updated']++;
                        } else {
                            SapDepartment::create([
                                'sap_code' => $centerCode,
                                'name' => $centerName,
                                'is_active' => true,
                                'synced_at' => now(),
                            ]);
                            $stats['created']++;
                        }
                    } catch (\Exception $e) {
                        $stats['errors']++;
                        $stats['error_messages'][] = "Error processing department {$centerCode}: ".$e->getMessage();
                        Log::error('Error syncing SAP department', [
                            'department' => $sapDepartment,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                DB::commit();

                Log::info('SAP Departments sync completed', $stats);

                return [
                    'success' => true,
                    'message' => "Sync completed: {$stats['created']} created, {$stats['updated']} updated, {$stats['errors']} errors",
                    'stats' => $stats,
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('SAP Departments sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Sync failed: '.$e->getMessage(),
                'stats' => [
                    'total' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'errors' => 1,
                    'error_messages' => [$e->getMessage()],
                ],
            ];
        }
    }
}
