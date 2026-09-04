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

class SyncSapDoFromGrpoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var array{trigger?: string, triggered_by_user_id?: int} */
    protected array $auditContext;

    public function __construct(
        protected string $targetDate,
        array $auditContext = []
    ) {
        $this->auditContext = $auditContext;
        $this->onQueue('default');
    }

    public function handle(SapService $sapService): void
    {
        $effectiveUserId = $this->auditContext['triggered_by_user_id'] ?? (Auth::id() ?? 1);
        $method = 'sql_server_direct';

        try {
            $results = $sapService->executeGrpoDoSqlQuery($this->targetDate);
            Log::channel('sap')->info('GRPO DO sync query successful, found '.count($results).' records');

            $doTypeId = $this->getDoTypeId();
            $successCount = 0;
            $skippedCount = 0;

            DB::beginTransaction();

            foreach ($results as $row) {
                $grpoNo = $row['DocNum'] ?? null;
                if (! $grpoNo) {
                    Log::channel('sap')->warning('Skipping GRPO row without DocNum: '.json_encode($row));

                    continue;
                }

                if (AdditionalDocument::query()
                    ->where('type_id', $doTypeId)
                    ->where('grpo_no', (string) $grpoNo)
                    ->exists()) {
                    $skippedCount++;

                    continue;
                }

                $document = new AdditionalDocument([
                    'type_id' => $doTypeId,
                    'document_number' => $row['NumAtCard'] ?? null,
                    'document_date' => $this->convertDate($row['TaxDate'] ?? null),
                    'receive_date' => $this->convertDate($row['DocDate'] ?? null),
                    'po_no' => $row['PoNo'] ?? null,
                    'vendor_code' => $row['CardCode'] ?? null,
                    'project' => $row['Project'] ?? null,
                    'grpo_no' => (string) $grpoNo,
                    'cur_loc' => '000HACC',
                    'created_by' => $effectiveUserId,
                    'status' => 'open',
                    'remarks' => 'Auto-sync dari GRPO '.$grpoNo,
                ]);
                $document->save();
                $successCount++;
            }

            DB::commit();

            DB::table('sap_logs')->insert([
                'action' => 'query_sync',
                'status' => 'success',
                'request_payload' => json_encode($this->baseAuditPayload($method, $effectiveUserId)),
                'response_payload' => json_encode([
                    'fetched' => count($results),
                    'success' => $successCount,
                    'skipped' => $skippedCount,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('sap')->error('GRPO DO sync failed: '.$e->getMessage());

            DB::table('sap_logs')->insert([
                'action' => 'query_sync',
                'status' => 'failed',
                'request_payload' => json_encode(array_merge(
                    $this->baseAuditPayload($method, $effectiveUserId),
                    ['error_context' => 'sync_exception']
                )),
                'error_message' => $e->getMessage(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function baseAuditPayload(string $method, int $effectiveUserId): array
    {
        return [
            'target_date' => $this->targetDate,
            'method' => $method,
            'sync_type' => 'grpo_do',
            'trigger' => $this->auditContext['trigger'] ?? 'web',
            'triggered_by_user_id' => $effectiveUserId,
            'synced_at' => now()->toIso8601String(),
        ];
    }

    private function getDoTypeId(): int
    {
        $doType = AdditionalDocumentType::query()
            ->where('type_name', 'Delivery Order (DO)')
            ->first();

        if (! $doType) {
            throw new \Exception('Delivery Order (DO) type not found');
        }

        return $doType->id;
    }

    private function convertDate(mixed $date): ?string
    {
        if ($date) {
            $timestamp = strtotime((string) $date);

            if ($timestamp) {
                return date('Y-m-d', $timestamp);
            }
        }

        return null;
    }
}
