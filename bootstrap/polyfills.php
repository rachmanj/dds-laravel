<?php

// Compatibility shims for setasign/fpdf 1.8 (locked in composer.lock), which calls
// magic-quotes functions removed in PHP 8.0+. Loaded globally (web, queue worker,
// and tests) so Fpdi/FPDF page splitting works on PHP 8.0+.

if (! function_exists('get_magic_quotes_runtime')) {
    function get_magic_quotes_runtime(): bool
    {
        return false;
    }
}

if (! function_exists('set_magic_quotes_runtime')) {
    function set_magic_quotes_runtime($new_setting): bool
    {
        return false;
    }
}
