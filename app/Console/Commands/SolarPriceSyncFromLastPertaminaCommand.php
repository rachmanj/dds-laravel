<?php

namespace App\Console\Commands;

use App\Models\SolarPriceHistory;
use App\Models\User;
use App\Services\PertaminaSolarInvoiceResolver;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SolarPriceSyncFromLastPertaminaCommand extends Command
{
    protected $signature = 'solar:price:sync-from-last-pertamina
                            {--force : Create even if a record already exists for the same invoice, line, and period}';

    protected $description = 'Create a solar price history from the latest PERTAMINA invoice line with SOLAR, for the current half-month period (1–14 or 15–EOM) in the scheduler timezone.';

    public function handle(PertaminaSolarInvoiceResolver $resolver): int
    {
        $tz = (string) config('services.solar_price_scheduler.timezone', 'Asia/Makassar');
        $now = Carbon::now($tz);
        [$periodStart, $periodEnd] = $this->currentHalfMonthBounds($now);

        $this->info(sprintf(
            'Using period %s → %s (%s, now %s).',
            $periodStart,
            $periodEnd,
            $tz,
            $now->toDateTimeString()
        ));

        $resolved = $resolver->resolveLast();
        if (! $resolved) {
            $this->error('No PERTAMINA invoice with a SOLAR line was found.');

            return self::FAILURE;
        }

        $unitPrice = $resolved['unit_price'] ?? null;
        if ($unitPrice === null) {
            $this->error('The matched line has no unit price; amount/quantity could not derive one.');

            return self::FAILURE;
        }

        $invoice = $resolved['invoice'];
        $line = $resolved['line'];

        if (! $this->option('force')) {
            $exists = SolarPriceHistory::query()
                ->where('invoice_id', $invoice->id)
                ->where('invoice_line_detail_id', $line->id)
                ->whereDate('period_start', $periodStart)
                ->whereDate('period_end', $periodEnd)
                ->exists();
            if ($exists) {
                $this->warn('A solar price history already exists for this invoice, line, and period. Use --force to add another row.');

                return self::SUCCESS;
            }
        }

        $creatorId = $this->resolveCreatorUserId();
        if (! $creatorId) {
            $this->error('No user available for created_by. Set SOLAR_PRICE_SCHEDULER_USER_ID or ensure a superadmin user exists.');

            return self::FAILURE;
        }

        SolarPriceHistory::query()->create([
            'invoice_id' => $invoice->id,
            'invoice_line_detail_id' => $line->id,
            'unit_price' => $unitPrice,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'quantity' => $line->quantity,
            'amount' => $line->amount,
            'notes' => sprintf('[Auto] %s / scheduler half-month', $now->toDateTimeString()),
            'created_by' => $creatorId,
        ]);

        $this->info(sprintf(
            'Created solar price history: invoice %s, line #%s, unit price %s, period %s → %s.',
            $invoice->invoice_number,
            (string) $line->line_no,
            (string) $unitPrice,
            $periodStart,
            $periodEnd
        ));

        return self::SUCCESS;
    }

    /**
     * First–14th or 15th–end of month (1–14 and 15–EOM) containing $now.
     *
     * @return array{0: string, 1: string} Y-m-d, Y-m-d
     */
    private function currentHalfMonthBounds(Carbon $now): array
    {
        $d = (int) $now->format('d');
        $y = (int) $now->format('Y');
        $m = (int) $now->format('m');

        if ($d <= 14) {
            $start = sprintf('%04d-%02d-01', $y, $m);
            $end = sprintf('%04d-%02d-14', $y, $m);

            return [$start, $end];
        }

        $endOf = $now->copy()->endOfMonth();
        $start = sprintf('%04d-%02d-15', $y, $m);
        $end = $endOf->toDateString();

        return [$start, $end];
    }

    private function resolveCreatorUserId(): ?int
    {
        $configured = config('services.solar_price_scheduler.creator_user_id');
        if (is_numeric($configured) && (int) $configured > 0) {
            $id = (int) $configured;
            if (User::query()->whereKey($id)->exists()) {
                return $id;
            }
        }

        $super = User::query()
            ->whereHas('roles', function ($q): void {
                $q->where('name', 'superadmin');
            })
            ->orderBy('id')
            ->first();

        return $super?->id ?? User::query()->orderBy('id')->value('id');
    }
}
