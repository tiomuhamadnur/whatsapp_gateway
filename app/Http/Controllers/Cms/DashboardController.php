<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\WhatsappSession;
use App\Services\QuotaService;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, QuotaService $quota): View
    {
        $user = $request->user();

        $activeSubscription = $user->subscriptions()
            ->with('productPlan')
            ->where('is_active', true)
            ->latest('ends_at')
            ->first();
        $dailyQuota = (int) ($activeSubscription?->productPlan?->daily_message_quota ?? $activeSubscription?->message_quota ?? 0);
        $usedToday = (int) ($activeSubscription?->messages_used_today ?? 0);

        $period = collect(CarbonPeriod::create(now()->subDays(29)->startOfDay(), now()->startOfDay()));
        $messageTrendRows = Message::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'day');
        $messageTrend = $period->map(fn ($date): array => [
            'label' => $date->format('M j'),
            'value' => (int) ($messageTrendRows[$date->toDateString()] ?? 0),
        ])->values();

        $statusCounts = Message::query()
            ->where('user_id', $user->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total);

        return view('cms.dashboard', [
            'sessionCount' => WhatsappSession::query()->where('user_id', $user->id)->count(),
            'connectedCount' => WhatsappSession::query()->where('user_id', $user->id)->where('status', 'connected')->count(),
            'queuedCount' => Message::query()->where('user_id', $user->id)->where('status', 'queued')->count(),
            'sentCount' => Message::query()->where('user_id', $user->id)->where('status', 'sent')->count(),
            'remainingQuota' => $quota->getRemainingQuota($user),
            'dailyQuota' => $dailyQuota,
            'usedToday' => $usedToday,
            'messageTrend' => $messageTrend,
            'statusCounts' => $statusCounts,
            'recentMessages' => Message::query()->where('user_id', $user->id)->latest()->limit(8)->get(),
            'recentSessions' => WhatsappSession::query()->where('user_id', $user->id)->latest()->limit(6)->get(),
        ]);
    }
}
