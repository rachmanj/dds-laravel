<?php

namespace App\Support;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class InvoiceListFilters
{
    /**
     * @return Builder<Invoice>
     */
    public static function baseQuery(): Builder
    {
        $arrivalDate = DocumentLocationMetricsSql::arrivalDateExpression('invoice');
        $daysInLocation = DocumentLocationMetricsSql::daysInLocationExpression('invoice');

        return Invoice::query()
            ->select('invoices.*')
            ->selectRaw("{$arrivalDate} as arrival_date")
            ->selectRaw("{$daysInLocation} as days_in_location")
            ->with(['supplier', 'type', 'creator', 'attachments']);
    }

    /**
     * @param  Builder<Invoice>  $query
     */
    public static function apply(Request $request, Builder $query): void
    {
        if ($request->filled('search_invoice_number')) {
            $query->where('invoice_number', 'like', '%'.$request->search_invoice_number.'%');
        }

        if ($request->filled('search_faktur_no')) {
            $query->where('faktur_no', 'like', '%'.$request->search_faktur_no.'%');
        }

        if ($request->filled('search_po_no')) {
            $query->where('po_no', 'like', '%'.$request->search_po_no.'%');
        }

        if ($request->filled('search_type')) {
            $query->whereHas('type', function ($q) use ($request) {
                $q->where('type_name', $request->search_type);
            });
        }

        if ($request->filled('search_status')) {
            $query->where('status', $request->search_status);
        }

        if ($request->filled('search_supplier')) {
            $query->whereHas('supplier', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search_supplier.'%');
            });
        }

        if ($request->filled('search_invoice_project')) {
            $query->where('invoice_project', $request->search_invoice_project);
        }

        if ($request->filled('filter_distribution_status')) {
            $query->where('distribution_status', $request->filter_distribution_status);
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
                $allowedDateTypes = ['created_at', 'invoice_date', 'receive_date'];
                if (in_array($dateType, $allowedDateTypes, true)) {
                    $query->whereBetween($dateType, [$startDate, $endDate]);
                }
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
