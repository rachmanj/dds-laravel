<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class InvoiceListScope
{
    public static function shouldSkipLocationFilter(User $user, bool $showAll): bool
    {
        if ($user->hasAnyRole(['superadmin', 'admin'])) {
            return true;
        }

        return $showAll && $user->can('see-all-record-switch');
    }

    public static function applyLocationFilter(Builder $query, User $user, bool $showAll): void
    {
        if (self::shouldSkipLocationFilter($user, $showAll)) {
            return;
        }

        $locationCode = $user->department_location_code;
        if ($locationCode) {
            $query->where('cur_loc', $locationCode);
        }
    }
}
