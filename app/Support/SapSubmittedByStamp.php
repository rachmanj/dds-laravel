<?php

namespace App\Support;

use Carbon\CarbonInterface;

class SapSubmittedByStamp
{
    public static function make(string $username, ?CarbonInterface $submittedAt = null): ?string
    {
        $username = trim($username);
        if ($username === '') {
            return null;
        }

        $at = $submittedAt !== null
            ? $submittedAt->copy()->timezone(config('app.timezone'))
            : now(config('app.timezone'));

        $suffix = ' '.$at->format('d/m/Y H:i');
        $maxUsernameLength = 30 - strlen($suffix);
        if ($maxUsernameLength < strlen($username)) {
            $username = substr($username, 0, $maxUsernameLength);
        }

        return $username.$suffix;
    }
}
