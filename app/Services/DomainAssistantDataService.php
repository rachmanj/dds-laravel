<?php

namespace App\Services;

use App\Models\AdditionalDocument;
use App\Models\Distribution;
use App\Models\Invoice;
use App\Models\ReconcileDetail;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DomainAssistantDataService
{
    private const MAX_DATE_RANGE_DAYS = 90;

    /**
     * @return array<string, mixed>
     */
    public function getDomainSummary(User $user, bool $showAllRecords): array
    {
        $expandAll = $this->expandAllLocationsEffective($user, $showAllRecords);

        $summary = [
            'scope' => [
                'expand_all_locations' => $expandAll,
                'note' => 'Invoice and additional-document visibility matches the list screens: location filters and (for additional documents) distribution_status rules apply unless the user is accounting/finance/admin/superadmin, or the assistant request used “show all records” with see-all-record-switch permission.',
            ],
        ];

        if ($user->can('view-invoices')) {
            $summary['invoices_visible_count'] = $this->invoicesVisibleQuery($user, $showAllRecords)->count();
        } else {
            $summary['invoices_visible_count'] = null;
            $summary['invoices_note'] = 'No permission to view invoices.';
        }

        if ($user->can('view-additional-documents')) {
            $summary['additional_documents_visible_count'] = $this->additionalDocumentsVisibleQuery($user, $showAllRecords)->count();
        } else {
            $summary['additional_documents_visible_count'] = null;
            $summary['additional_documents_note'] = 'No permission to view additional documents.';
        }

        if ($user->can('view-distributions')) {
            $summary['distributions_visible_count'] = $this->distributionsVisibleQuery($user)->count();
        } else {
            $summary['distributions_visible_count'] = null;
            $summary['distributions_note'] = 'No permission to view distributions.';
        }

        if ($user->can('view-reconcile')) {
            $summary['reconcile_rows_visible_count'] = $this->reconcileVisibleQuery($user)->count();
        } else {
            $summary['reconcile_rows_visible_count'] = null;
            $summary['reconcile_note'] = 'No permission to view reconcile data.';
        }

        if ($user->can('view-suppliers')) {
            $summary['active_suppliers_count'] = Supplier::active()->count();
        } else {
            $summary['active_suppliers_count'] = null;
            $summary['suppliers_note'] = 'No permission to view suppliers.';
        }

        return $summary;
    }

    /**
     * @return list<array<string, mixed>>|array{error: string}
     */
    public function searchInvoices(
        User $user,
        bool $showAllRecords,
        ?string $status,
        int $limit,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $supplierQuery = null
    ): array {
        if (! $user->can('view-invoices')) {
            return ['error' => 'You do not have permission to view invoices.'];
        }

        $limit = max(1, min(20, $limit));
        $query = $this->invoicesVisibleQuery($user, $showAllRecords);

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($supplierQuery !== null && trim($supplierQuery) !== '') {
            $term = trim($supplierQuery);
            $pattern = '%'.addcslashes($term, '%_\\').'%';
            $query->whereHas('supplier', function (Builder $sub) use ($pattern) {
                $sub->where('name', 'like', $pattern)
                    ->orWhere('sap_code', 'like', $pattern);
            });
        }

        $rangeError = $this->applyInvoiceDateRange($query, $dateFrom, $dateTo);
        if ($rangeError !== null) {
            return ['error' => $rangeError];
        }

        return $query->with('supplier')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Invoice $inv) => [
                'invoice_number' => $inv->invoice_number,
                'faktur_no' => $inv->faktur_no,
                'invoice_date' => $inv->invoice_date?->format('Y-m-d'),
                'status' => $inv->status,
                'distribution_status' => $inv->distribution_status,
                'supplier' => $inv->supplier?->name,
                'amount' => $inv->amount !== null ? (string) $inv->amount : null,
                'currency' => $inv->currency,
                'cur_loc' => $inv->cur_loc,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>|array{error: string}
     */
    public function searchAdditionalDocuments(
        User $user,
        bool $showAllRecords,
        ?string $status,
        int $limit,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        if (! $user->can('view-additional-documents')) {
            return ['error' => 'You do not have permission to view additional documents.'];
        }

        $limit = max(1, min(20, $limit));
        $query = $this->additionalDocumentsVisibleQuery($user, $showAllRecords);

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $rangeError = $this->applyAdditionalDocumentDateRange($query, $dateFrom, $dateTo);
        if ($rangeError !== null) {
            return ['error' => $rangeError];
        }

        return $query->with('type')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (AdditionalDocument $doc) => [
                'document_number' => $doc->document_number,
                'document_date' => $doc->document_date?->format('Y-m-d'),
                'status' => $doc->status,
                'distribution_status' => $doc->distribution_status,
                'type' => $doc->type?->type_name,
                'vendor_code' => $doc->vendor_code,
                'cur_loc' => $doc->cur_loc,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>|array{error: string}
     */
    public function searchDistributions(
        User $user,
        ?string $status,
        int $limit,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        if (! $user->can('view-distributions')) {
            return ['error' => 'You do not have permission to view distributions.'];
        }

        $limit = max(1, min(20, $limit));
        $query = $this->distributionsVisibleQuery($user);

        if ($status !== null && $status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        $rangeError = $this->applyCreatedAtDateRange($query, $dateFrom, $dateTo);
        if ($rangeError !== null) {
            return ['error' => $rangeError];
        }

        return $query->with(['type', 'originDepartment', 'destinationDepartment'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Distribution $d) => [
                'distribution_number' => $d->distribution_number,
                'status' => $d->status,
                'year' => $d->year,
                'type' => $d->type?->name,
                'origin_department' => $d->originDepartment?->name,
                'destination_department' => $d->destinationDepartment?->name,
                'created_at' => $d->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>|array{error: string}
     */
    public function searchReconcileRecords(
        User $user,
        ?string $invoiceNoFragment,
        int $limit,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        if (! $user->can('view-reconcile')) {
            return ['error' => 'You do not have permission to view reconcile data.'];
        }

        $limit = max(1, min(20, $limit));
        $query = $this->reconcileVisibleQuery($user);

        if ($invoiceNoFragment !== null && $invoiceNoFragment !== '') {
            $query->where('invoice_no', 'like', '%'.$invoiceNoFragment.'%');
        }

        $rangeError = $this->applyCreatedAtDateRange($query, $dateFrom, $dateTo);
        if ($rangeError !== null) {
            return ['error' => $rangeError];
        }

        return $query->with('supplier')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (ReconcileDetail $r) => [
                'invoice_no' => $r->invoice_no,
                'invoice_date' => $r->invoice_date?->format('Y-m-d'),
                'vendor' => $r->supplier?->name,
                'created_at' => $r->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>|array{error: string}
     */
    public function searchSuppliers(User $user, ?string $queryText, int $limit): array
    {
        if (! $user->can('view-suppliers')) {
            return ['error' => 'You do not have permission to view suppliers.'];
        }

        $limit = max(1, min(20, $limit));
        $q = Supplier::active();

        if ($queryText !== null && trim($queryText) !== '') {
            $term = trim($queryText);
            $words = array_values(array_filter(
                preg_split('/\s+/u', preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $term)),
                fn (string $w) => mb_strlen($w) >= 2
            ));

            if (count($words) >= 2) {
                foreach ($words as $w) {
                    $pw = self::supplierLikePattern($w);
                    $q->where(function (Builder $sub) use ($pw) {
                        $sub->where('name', 'like', $pw)
                            ->orWhere('sap_code', 'like', $pw);
                    });
                }
                $q->orderBy('name');
            } else {
                $pattern = self::supplierLikePattern($term);
                $q->where(function (Builder $sub) use ($pattern, $term) {
                    $sub->where('name', 'like', $pattern)
                        ->orWhere('sap_code', 'like', $pattern);
                    if (self::isLikelySapCodeToken($term)) {
                        $sub->orWhereRaw('LOWER(TRIM(sap_code)) = LOWER(?)', [$term]);
                    }
                });
                $q->orderByRaw(
                    'CASE WHEN LOWER(TRIM(sap_code)) = LOWER(?) THEN 0 WHEN LOWER(TRIM(name)) = LOWER(?) THEN 1 ELSE 2 END, name',
                    [$term, $term]
                );
            }
        } else {
            $q->orderBy('name');
        }

        return $q->limit($limit)
            ->get()
            ->map(fn (Supplier $s) => [
                'name' => $s->name,
                'sap_code' => $s->sap_code,
                'type' => $s->type,
                'city' => $s->city,
                'payment_project' => $s->payment_project,
            ])
            ->values()
            ->all();
    }

    private static function supplierLikePattern(string $term): string
    {
        return '%'.addcslashes($term, '%_\\').'%';
    }

    private static function isLikelySapCodeToken(string $term): bool
    {
        if (str_contains($term, ' ') || mb_strlen($term) < 6 || mb_strlen($term) > 32) {
            return false;
        }

        return (bool) preg_match('/^[A-Z0-9][A-Z0-9\-]*$/i', $term);
    }

    private function reconcileVisibleQuery(User $user): Builder
    {
        return ReconcileDetail::query()
            ->forUser($user->id)
            ->withoutFlag();
    }

    private function invoicesVisibleQuery(User $user, bool $showAllRecords): Builder
    {
        $query = Invoice::query();

        $skipLocation = $user->hasAnyRole(['superadmin', 'admin', 'accounting', 'finance'])
            || $this->expandAllLocationsEffective($user, $showAllRecords);

        if (! $skipLocation) {
            $locationCode = $user->department_location_code;
            if ($locationCode) {
                $query->where('cur_loc', $locationCode);
            }
        }

        return $query;
    }

    private function additionalDocumentsVisibleQuery(User $user, bool $showAllRecords): Builder
    {
        $query = AdditionalDocument::query();

        $isPrivilegedUser = $user->hasAnyRole(['admin', 'superadmin', 'accounting', 'finance']);

        $restrict = ! $isPrivilegedUser
            && (! $this->expandAllLocationsEffective($user, $showAllRecords));

        if ($restrict) {
            $locationCode = $user->department_location_code;
            if ($locationCode) {
                $query->where('cur_loc', $locationCode);
            }
            $query->whereIn('distribution_status', ['available', 'distributed']);
        }

        return $query;
    }

    private function distributionsVisibleQuery(User $user): Builder
    {
        $query = Distribution::query();

        $roleNames = $user->roles->pluck('name')->toArray();
        if (! array_intersect($roleNames, ['superadmin', 'admin'])) {
            if ($user->department) {
                $query->where(function ($q) use ($user) {
                    $q->where(function ($subQ) use ($user) {
                        $subQ->where('destination_department_id', $user->department->id)
                            ->where('status', 'sent');
                    })->orWhere(function ($subQ) use ($user) {
                        $subQ->where('origin_department_id', $user->department->id)
                            ->whereIn('status', ['draft', 'sent']);
                    });
                });
            }
        }

        return $query;
    }

    private function expandAllLocationsEffective(User $user, bool $showAllRecords): bool
    {
        return $showAllRecords && $user->can('see-all-record-switch');
    }

    private function applyInvoiceDateRange(Builder $query, ?string $dateFrom, ?string $dateTo): ?string
    {
        $bounds = $this->resolveDateBounds($dateFrom, $dateTo);
        if ($bounds === false) {
            return 'Invalid date range. Use YYYY-MM-DD; maximum span is '.self::MAX_DATE_RANGE_DAYS.' days.';
        }
        if ($bounds === null) {
            return null;
        }
        [$start, $end] = $bounds;
        $query->where(function (Builder $q) use ($start, $end) {
            $q->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
                ->orWhere(function (Builder $q2) use ($start, $end) {
                    $q2->whereNull('invoice_date')
                        ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
                });
        });

        return null;
    }

    private function applyAdditionalDocumentDateRange(Builder $query, ?string $dateFrom, ?string $dateTo): ?string
    {
        $bounds = $this->resolveDateBounds($dateFrom, $dateTo);
        if ($bounds === false) {
            return 'Invalid date range. Use YYYY-MM-DD; maximum span is '.self::MAX_DATE_RANGE_DAYS.' days.';
        }
        if ($bounds === null) {
            return null;
        }
        [$start, $end] = $bounds;
        $query->where(function (Builder $q) use ($start, $end) {
            $q->whereBetween('document_date', [$start->toDateString(), $end->toDateString()])
                ->orWhere(function (Builder $q2) use ($start, $end) {
                    $q2->whereNull('document_date')
                        ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
                });
        });

        return null;
    }

    private function applyCreatedAtDateRange(Builder $query, ?string $dateFrom, ?string $dateTo): ?string
    {
        $bounds = $this->resolveDateBounds($dateFrom, $dateTo);
        if ($bounds === false) {
            return 'Invalid date range. Use YYYY-MM-DD; maximum span is '.self::MAX_DATE_RANGE_DAYS.' days.';
        }
        if ($bounds === null) {
            return null;
        }
        [$start, $end] = $bounds;
        $query->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);

        return null;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}|null|false null=no filter, false=invalid
     */
    private function resolveDateBounds(?string $dateFrom, ?string $dateTo): array|null|false
    {
        $fromRaw = $dateFrom !== null && trim($dateFrom) !== '' ? trim($dateFrom) : null;
        $toRaw = $dateTo !== null && trim($dateTo) !== '' ? trim($dateTo) : null;

        if ($fromRaw === null && $toRaw === null) {
            return null;
        }

        try {
            $start = $fromRaw !== null ? Carbon::parse($fromRaw)->startOfDay() : Carbon::parse($toRaw)->startOfDay();
            $end = $toRaw !== null ? Carbon::parse($toRaw)->endOfDay() : Carbon::parse($fromRaw)->endOfDay();
        } catch (\Throwable) {
            return false;
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        if ($start->diffInDays($end) > self::MAX_DATE_RANGE_DAYS) {
            return false;
        }

        return [$start, $end];
    }
}
