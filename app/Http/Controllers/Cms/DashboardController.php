<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\WhatsappSession;
use App\Services\QuotaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, QuotaService $quota): View
    {
        $user = $request->user();

        return view('cms.dashboard', [
            'sessionCount' => WhatsappSession::query()->where('user_id', $user->id)->count(),
            'connectedCount' => WhatsappSession::query()->where('user_id', $user->id)->where('status', 'connected')->count(),
            'queuedCount' => Message::query()->where('user_id', $user->id)->where('status', 'queued')->count(),
            'sentCount' => Message::query()->where('user_id', $user->id)->where('status', 'sent')->count(),
            'remainingQuota' => $quota->getRemainingQuota($user),
            'recentMessages' => Message::query()->where('user_id', $user->id)->latest()->limit(8)->get(),
            'recentSessions' => WhatsappSession::query()->where('user_id', $user->id)->latest()->limit(6)->get(),
        ]);
    }
}
