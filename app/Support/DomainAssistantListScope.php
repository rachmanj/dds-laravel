<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

final class DomainAssistantListScope
{
    /**
     * Web assistant: “Show all records” checkbox + permission (same as invoice list UI).
     */
    public static function fromWebRequest(User $user, Request $request): bool
    {
        return $request->boolean('show_all_records')
            && $user->can('see-all-record-switch');
    }

    /**
     * Telegram: defaults to the same scope as web with the checkbox **off**.
     * Set `TELEGRAM_ASSISTANT_EXPAND_ALL_LOCATIONS=true` so users who have
     * `see-all-record-switch` get expanded invoice/additional-document scope on Telegram
     * (equivalent to having “Show all records” checked on web).
     */
    public static function forTelegram(User $user): bool
    {
        if (! $user->can('see-all-record-switch')) {
            return false;
        }

        return filter_var(config('services.telegram.expand_all_locations', false), FILTER_VALIDATE_BOOL);
    }
}
