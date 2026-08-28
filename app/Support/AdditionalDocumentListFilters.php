<?php

namespace App\Support;

use App\Models\AdditionalDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdditionalDocumentListFilters
{
    /**
     * @return Builder<AdditionalDocument>
     */
    public static function baseQuery(): Builder
    {
        $arrivalDate = DocumentLocationMetricsSql::arrivalDateExpression('additional-document');
        $daysInLocation = DocumentLocationMetricsSql::daysInLocationExpression('additional-document');

        return AdditionalDocument::query()
            ->select('additional_documents.*')
            ->selectRaw("{$arrivalDate} as arrival_date")
            ->selectRaw("{$daysInLocation} as days_in_location")
            ->with(['type', 'creator', 'invoices']);
    }

    /**
     * @param  Builder<AdditionalDocument>  $query
     */
    public static function apply(Request $request, Builder $query, ?User $user = null, bool $restrictByLocation = true, bool $showAllRecords = false): void
    {
        if ($request->filled('search_number')) {
            $query->where('document_number', 'like', '%'.$request->search_number.'%');
        }

        if ($request->filled('search_po_no')) {
            $query->where('po_no', 'like', '%'.$request->search_po_no.'%');
        }

        if ($request->filled('search_vendor_code')) {
            $query->where('vendor_code', 'like', '%'.$request->search_vendor_code.'%');
        }

        if ($request->filled('search_content')) {
            $query->where(function ($q) use ($request) {
                $q->where('remarks', 'like', '%'.$request->search_content.'%')
                    ->orWhere('attachment', 'like', '%'.$request->search_content.'%');
            });
        }

        if ($request->filled('filter_type')) {
            $query->where('type_id', $request->filter_type);
        }

        if ($request->filled('filter_status')) {
            $query->where('status', $request->filter_status);
        }

        if ($request->filled('filter_distribution_status')) {
            $query->where('distribution_status', $request->filter_distribution_status);
        }

        if ($request->filled('filter_vendor_code')) {
            $query->where('vendor_code', $request->filter_vendor_code);
        }

        if ($request->filled('filter_location')) {
            $query->where('cur_loc', $request->filter_location);
        }

        if ($request->filled('date_range')) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) === 2) {
                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[0])->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $dates[1])->endOfDay();

                $dateType = $request->get('date_type', 'created_at');
                $query->whereBetween($dateType, [$startDate, $endDate]);
            }
        }

        if ($request->filled('search_preset') && $user) {
            $preset = $request->search_preset;
            switch ($preset) {
                case 'recent':
                    $query->where('created_at', '>=', now()->subDays(30));
                    break;
                case 'open':
                    $query->where('status', 'open');
                    break;
                case 'my_department':
                    $locationCode = $user->department_location_code;
                    if ($locationCode) {
                        $query->where('cur_loc', $locationCode);
                    }
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('created_at', now()->subMonth()->month)
                        ->whereYear('created_at', now()->subMonth()->year);
                    break;
            }
        }

        if ($restrictByLocation && $user) {
            $isPrivilegedUser = $user->hasAnyRole(['admin', 'superadmin', 'accounting', 'finance']);

            if (! $isPrivilegedUser && (! $showAllRecords || ! $user->can('see-all-record-switch'))) {
                $locationCode = $user->department_location_code;
                if ($locationCode) {
                    $query->where('cur_loc', $locationCode);
                }
            }

            if (! $isPrivilegedUser && (! $showAllRecords || ! $user->can('see-all-record-switch'))) {
                $statuses = ['available', 'distributed'];
                if ($request->filled('age_filter')) {
                    $statuses[] = 'in_transit';
                }
                $query->whereIn('distribution_status', $statuses);
            }
        }

        if ($request->filled('age_filter')) {
            $ageFilter = $request->get('age_filter');
            $query->havingRaw('days_in_location >= 0');

            switch ($ageFilter) {
                case '0-7_days':
                    $query->havingRaw('days_in_location <= 7');
                    break;
                case '8-14_days':
                    $query->havingRaw('days_in_location > 7 AND days_in_location <= 14');
                    break;
                case '15_plus_days':
                case '15-30_days':
                    $query->havingRaw('days_in_location > 14');
                    break;
            }
        }
    }
}
