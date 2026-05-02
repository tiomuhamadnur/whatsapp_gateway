<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\WhatsappSession;
use Illuminate\View\View;

class SessionMonitorController extends Controller
{
    public function sessions(): View
    {
        return view('owner.sessions.index', [
            'sessions' => WhatsappSession::query()->with('user')->latest()->get(),
        ]);
    }

    public function messages(): View
    {
        return view('owner.messages.index', [
            'messages' => Message::query()->with('user')->latest()->limit(1000)->get(),
        ]);
    }
}
