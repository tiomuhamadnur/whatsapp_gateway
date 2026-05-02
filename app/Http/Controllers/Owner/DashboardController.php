<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\ProductPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WhatsappSession;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('owner.dashboard', [
            'userCount' => User::query()->count(),
            'activeSubscriptionCount' => Subscription::query()->where('is_active', true)->count(),
            'sessionCount' => WhatsappSession::query()->count(),
            'connectedSessionCount' => WhatsappSession::query()->where('status', 'connected')->count(),
            'messageCount' => Message::query()->count(),
            'sentTodayCount' => Message::query()->whereDate('created_at', today())->count(),
            'planCount' => ProductPlan::query()->where('is_active', true)->count(),
            'recentUsers' => User::query()->latest()->limit(8)->get(),
            'recentMessages' => Message::query()->with('user')->latest()->limit(10)->get(),
        ]);
    }
}
