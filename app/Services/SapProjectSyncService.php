<?php

namespace App\Services;

use App\Models\SapProject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SapProjectSyncService
{
    protected SapService $sapService;

    public function __construct(SapService $sapService)
    {
        $this->sapService = $sapService;
    }

    public function syncProjects(): array
    {
        try {
            $this->sapService->ensureSession();

            // Call SAP B1 ProjectsService_GetProjectList
            $response = $this->sapService->get('ProjectsService_GetProjectList');

            if (! isset($response['value']) && ! is_array($response)) {
                throw new \Exception('Invalid response format from SAP ProjectsService_GetProjectList');
            }

            $projects = $response['value'] ?? (is_array($response) ? $response : []);

            $stats = [
                'total' => count($projects),
                'created' => 0,
                'updated' => 0,
                'errors' => 0,
                'error_messages' => [],
            ];

            DB::beginTransaction();

            try {
                foreach ($projects as $sapProject) {
                    try {
                        $projectCode = $sapProject['ProjectCode'] ?? $sapProject['Code'] ?? null;
                        $projectName = $sapProject['ProjectName'] ?? $sapProject['Name'] ?? null;

                        if (! $projectCode || ! $projectName) {
                            $stats['errors']++;
                            $stats['error_messages'][] = 'Missing ProjectCode or ProjectName: '.json_encode($sapProject);

                            continue;
                        }

                        // Upsert by sap_code
                        $project = SapProject::where('sap_code', $projectCode)->first();

                        if ($project) {
                            $project->update([
                                'name' => $projectName,
                                'description' => $sapProject['ProjectDescription'] ?? $sapProject['Name'] ?? null,
                                'is_active' => $sapProject['Active'] ?? true,
                                'synced_at' => now(),
                            ]);
                            $stats['updated']++;
                        } else {
                            SapProject::create([
                                'sap_code' => $projectCode,
                                'name' => $projectName,
                                'description' => $sapProject['ProjectDescription'] ?? $sapProject['Name'] ?? null,
                                'is_active' => $sapProject['Active'] ?? true,
                                'synced_at' => now(),
                            ]);
                            $stats['created']++;
                        }
                    } catch (\Exception $e) {
                        $stats['errors']++;
                        $stats['error_messages'][] = "Error processing project {$projectCode}: ".$e->getMessage();
                        Log::error('Error syncing SAP project', [
                            'project' => $sapProject,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                DB::commit();

                Log::info('SAP Projects sync completed', $stats);

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
            Log::error('SAP Projects sync failed', [
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
