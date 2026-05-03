<?php

use App\Helpers\NumberFormatter;

if (!function_exists('formatLarge')) {
    function formatLarge(int|float $value): string
    {
        return NumberFormatter::formatLarge($value);
    }
}
