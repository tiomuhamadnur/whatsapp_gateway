<?php

namespace App\Http\Middleware;

use App\Services\QuotaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionQuota
{
    public function __construct(private readonly QuotaService $quota)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->quota->hasQuota($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Your message quota has been used up. Upgrade your plan to send more messages.',
                'code' => 'QUOTA_EXCEEDED',
            ], 402);
        }

        return $next($request);
    }
}
