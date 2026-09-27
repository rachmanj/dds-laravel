<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\SapService;
use App\Support\SapSubmittedByStamp;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class SapBackfillSubmittedByCommand extends Command
{
    protected $signature = 'sap:backfill-submitted-by
                            {--dry-run : Preview changes without writing to SAP}
                            {--write : Apply U_MIS_Submitted updates to SAP}
                            {--limit=0 : Maximum invoices to process (0 = all)}
                            {--sleep=250 : Milliseconds to wait between SAP API calls}';

    protected $description = 'Backfill SAP AP Invoice UDF U_MIS_Submitted from DDS submitter data for posted documents';

    public function handle(SapService $sapService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $write = (bool) $this->option('write');

        if (! $dryRun && ! $write) {
            $this->error('Refusing to run without an explicit mode. Use --dry-run to preview or --write to update SAP.');

            return self::FAILURE;
        }

        if ($dryRun && $write) {
            $this->error('Specify only one of --dry-run or --write.');

            return self::FAILURE;
        }

        $limit = max(0, (int) $this->option('limit'));
        $sleepMs = max(0, (int) $this->option('sleep'));

        $candidates = $this->candidateQuery($limit)->get();

        $wouldWrite = 0;
        $alreadyFilled = 0;
        $readFailed = 0;
        $written = 0;
        $writeFailed = 0;
        $skippedNoValue = 0;
        $sapCallCount = 0;

        foreach ($candidates as $invoice) {
            $invoice->loadMissing('sapSubmitter');

            $username = trim((string) ($invoice->sapSubmitter?->username ?? ''));
            $newValue = SapSubmittedByStamp::make($username, $invoice->sap_submitted_at);

            if ($newValue === null) {
                $skippedNoValue++;
                $this->warn("Skipping DDS invoice #{$invoice->id}: no username for U_MIS_Submitted.");

                continue;
            }

            $docEntry = (string) $invoice->sap_doc_entry;

            try {
                if ($sapCallCount > 0 && $sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }

                $sapRow = $sapService->getPurchaseInvoiceSubmittedUdf($docEntry);
                $sapCallCount++;
            } catch (RequestException $e) {
                $readFailed++;
                $this->error("Read failed DocEntry {$docEntry} (DDS #{$invoice->id}): ".$e->getMessage());

                continue;
            } catch (\Throwable $e) {
                $readFailed++;
                $this->error("Read failed DocEntry {$docEntry} (DDS #{$invoice->id}): ".$e->getMessage());

                continue;
            }

            if ($sapRow === null) {
                $readFailed++;
                $this->error("Read failed DocEntry {$docEntry} (DDS #{$invoice->id}): document not found in SAP.");

                continue;
            }

            $docNum = $sapRow['DocNum'] ?? $invoice->sap_doc_num;
            $currentSap = trim((string) ($sapRow['U_MIS_Submitted'] ?? ''));
            $currentDisplay = $currentSap === '' ? '(empty)' : $currentSap;

            if ($currentSap !== '') {
                $alreadyFilled++;
                if ($dryRun) {
                    $this->line(sprintf(
                        'DocEntry=%s DocNum=%s DDS=%s user=%s would_write=%s sap_now=%s [already filled]',
                        $docEntry,
                        $docNum,
                        $invoice->invoice_number,
                        $username,
                        $newValue,
                        $currentDisplay
                    ));
                } else {
                    $this->line("Skip DocEntry {$docEntry}: U_MIS_Submitted already set to {$currentDisplay}");
                }

                continue;
            }

            if ($newValue === $currentSap) {
                continue;
            }

            $wouldWrite++;

            if ($dryRun) {
                $this->line(sprintf(
                    'DocEntry=%s DocNum=%s DDS=%s user=%s would_write=%s sap_now=%s',
                    $docEntry,
                    $docNum,
                    $invoice->invoice_number,
                    $username,
                    $newValue,
                    $currentDisplay
                ));

                continue;
            }

            try {
                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }

                $sapService->updateApInvoice($docEntry, ['U_MIS_Submitted' => $newValue]);
                $sapCallCount++;
                $written++;
                $this->info("OK DocEntry {$docEntry}: U_MIS_Submitted set to {$newValue}");
            } catch (RequestException $e) {
                $writeFailed++;
                $this->error("PATCH failed DocEntry {$docEntry} (DDS #{$invoice->id}): ".$e->getMessage());
            } catch (\Throwable $e) {
                $writeFailed++;
                $this->error("PATCH failed DocEntry {$docEntry} (DDS #{$invoice->id}): ".$e->getMessage());
            }
        }

        $candidateCount = $candidates->count();

        if ($dryRun) {
            $this->newLine();
            $this->info(sprintf(
                'Summary: candidates=%d, would_write=%d, already_filled=%d, read_failed=%d, skipped_no_username=%d',
                $candidateCount,
                $wouldWrite,
                $alreadyFilled,
                $readFailed,
                $skippedNoValue
            ));
        } else {
            $this->newLine();
            $this->info(sprintf(
                'Summary: candidates=%d, written=%d, write_failed=%d, already_filled=%d, read_failed=%d, skipped_no_username=%d',
                $candidateCount,
                $written,
                $writeFailed,
                $alreadyFilled,
                $readFailed,
                $skippedNoValue
            ));
        }

        return self::SUCCESS;
    }

    /**
     * @return Builder<Invoice>
     */
    protected function candidateQuery(int $limit): Builder
    {
        $query = Invoice::query()
            ->with('sapSubmitter')
            ->where('sap_status', 'posted')
            ->whereNotNull('sap_doc_entry')
            ->whereNotNull('sap_doc_num')
            ->whereNotNull('sap_submitted_by_user_id')
            ->whereNotNull('sap_submitted_at')
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query;
    }
}
