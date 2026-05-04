<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\ProductPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WhatsappSession;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $period = collect(CarbonPeriod::create(now()->subDays(29)->startOfDay(), now()->startOfDay()));
        $messageTrendRows = Message::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'day');

        return view('owner.dashboard', [
            'userCount' => User::query()->count(),
            'activeSubscriptionCount' => Subscription::query()->where('is_active', true)->count(),
            'sessionCount' => WhatsappSession::query()->count(),
            'connectedSessionCount' => WhatsappSession::query()->where('status', 'connected')->count(),
            'messageCount' => Message::query()->count(),
            'sentTodayCount' => Message::query()->whereDate('created_at', today())->count(),
            'planCount' => ProductPlan::query()->where('is_active', true)->count(),
            'messageTrend' => $period->map(fn ($date): array => [
                'label' => $date->format('M j'),
                'value' => (int) ($messageTrendRows[$date->toDateString()] ?? 0),
            ])->values(),
            'planDistribution' => Subscription::query()
                ->with('productPlan')
                ->where('is_active', true)
                ->get()
                ->groupBy(fn (Subscription $subscription): string => $subscription->productPlan?->name ?: $subscription->plan_name)
                ->map(fn ($items): int => $items->count()),
            'recentUsers' => User::query()->latest()->limit(8)->get(),
            'recentMessages' => Message::query()->with('user')->latest()->limit(10)->get(),
        ]);
    }
}
