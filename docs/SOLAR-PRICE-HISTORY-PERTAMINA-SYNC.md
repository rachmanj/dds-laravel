# Solar price history — PERTAMINA line sync

**Status**: Implemented (2026)  
**Scope**: Create `solar_price_histories` rows from the **latest** PERTAMINA supplier invoice that contains an **`invoice_line_details`** row whose **description** matches **`%SOLAR%`**. Complements manual CRUD in the app and the Domain Assistant **active solar unit price** tools.

**Live / ops note**: The scheduled job runs in **production** the same as other Laravel schedules: ensure the OS or hosting **cron** invokes `php artisan schedule:run` every minute so `dailyAt` fires at the correct local time. Overlaps are guarded with `withoutOverlapping()`.

## Artisan command

| Item | Value |
|------|--------|
| **Signature** | `php artisan solar:price:sync-from-last-pertamina` |
| **Option** | `--force` — insert even if a row already exists for the same `invoice_id`, `invoice_line_detail_id`, `period_start`, and `period_end` |
| **Class** | [`app/Console/Commands/SolarPriceSyncFromLastPertaminaCommand.php`](../app/Console/Commands/SolarPriceSyncFromLastPertaminaCommand.php) |

**Behaviour summary**:

1. **Timezone** — `now` and period boundaries use `config('services.solar_price_scheduler.timezone', 'Asia/Makassar')` (overridable with `SOLAR_PRICE_SCHEDULER_TIMEZONE`).
2. **Period (default)** — **Half-month** windows: **1–14** or **15–end of month** of the month containing `now` in that timezone.
3. **Source invoice** — [`App\Services\PertaminaSolarInvoiceResolver`](../app/Services/PertaminaSolarInvoiceResolver.php) `resolveLast()`: supplier name **`PERTAMINA`**, newest matching invoice by `id`, first matching SOLAR line (ordered by `line_no`). API **`GET`** preview for “last PERTAMINA solar” uses the same resolver (see `SolarPriceHistoryController::fetchLastPertaminaSolar`).
4. **Unit price** — Stored **`solar_price_histories.unit_price`** comes from the resolver: use **`invoice_line_details.unit_price`** when it is **non-null and not zero**; otherwise **`amount ÷ quantity`** with **`bcdiv(..., 4)`** when both are present and quantity ≠ 0. This covers lines still null in the UI before a user enters an explicit unit price, as long as line amount and quantity are filled.
5. **Idempotency** — Unless **`--force`**, if a history row already exists for the same invoice, line, and period dates, the command **exits successfully without inserting** and prints a warning.
6. **`created_by`** — From `config('services.solar_price_scheduler.creator_user_id')` if that user id exists, else first **`superadmin`**, else first user. Set **`SOLAR_PRICE_SCHEDULER_USER_ID`** in production if a dedicated service user is preferred (see [`.env.example`](../.env.example)).

**Failure cases** (non-zero exit): no PERTAMINA + SOLAR match; unit price could not be resolved (no line price and cannot derive from amount/quantity); no user for `created_by`.

## Scheduler

Registered in [`bootstrap/app.php`](../bootstrap/app.php) `withSchedule` (same **`Asia/Makassar`** variable as `sap:sync-ito`):

- **`solar:price:sync-from-last-pertamina`** — `dailyAt('07:30')`, `timezone('Asia/Makassar')`, `withoutOverlapping()`.

To verify: `php artisan schedule:list`.

## Configuration

[`config/services.php`](../config/services.php) key **`solar_price_scheduler`**:

- **`creator_user_id`** ← `SOLAR_PRICE_SCHEDULER_USER_ID`
- **`timezone`** ← `SOLAR_PRICE_SCHEDULER_TIMEZONE` (default `Asia/Makassar`)

## Related code and docs

- Model: [`app/Models/SolarPriceHistory.php`](../app/Models/SolarPriceHistory.php)
- Resolver unit-price rules: `PertaminaSolarInvoiceResolver::resolveUnitPrice`
- Architecture overview: [`docs/architecture.md`](architecture.md) (section **Solar price history (PERTAMINA auto-sync)**)
- Decision record: [`docs/decisions.md`](decisions.md) (2026-04-22)
- Task log: [`docs/todo.md`](todo.md) (Recently completed)

## Reference: Domain Assistant

Active solar price for “today” is derived from `solar_price_histories` (period range). If this command keeps histories aligned with the latest PERTAMINA SOLAR line, assistant answers stay consistent with finance data when schedules run as expected.
