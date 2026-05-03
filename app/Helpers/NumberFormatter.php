<?php

namespace App\Helpers;

class NumberFormatter
{
    /**
     * Format large numbers to human-readable format (1.2K, 3.4M, 5.6B)
     */
    public static function formatLarge(int|float $value): string
    {
        if ($value < 1000) {
            return (string) intval($value);
        }

        $units = ['K', 'M', 'B', 'T'];
        $value = (float) $value;

        foreach ($units as $unit) {
            if ($value < 1000) {
                return number_format($value, 1) . $unit;
            }
            $value /= 1000;
        }

        return number_format($value, 1) . 'Q';
    }
}
