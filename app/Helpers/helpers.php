<?php

use App\Helpers\NumberFormatter;

if (!function_exists('formatLarge')) {
    function formatLarge(int|float $value): string
    {
        return NumberFormatter::formatLarge($value);
    }
}

if (!function_exists('statusBadge')) {
    function statusBadge(string $status): string
    {
        return match (strtolower($status)) {
            'connected', 'sent', 'active' => 'bg-emerald-100 text-emerald-800',
            'queued' => 'bg-amber-100 text-amber-800',
            'scheduled', 'sending', 'qr_ready' => 'bg-sky-100 text-sky-800',
            'disconnected', 'failed', 'inactive' => 'bg-red-100 text-red-800',
            default => 'bg-zinc-100 text-zinc-700',
        };
    }
}
