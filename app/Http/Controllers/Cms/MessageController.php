<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Message;
use App\Models\MessageLog;
use App\Models\WhatsappSession;
use App\Services\QuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $query = Message::query()
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->query('session_id'));
        }

        return view('cms.messages.index', [
            'messages' => $query->paginate(15)->withQueryString(),
            'sessions' => WhatsappSession::query()
                ->where('user_id', $request->user()->id)
                ->where('status', 'connected')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request, QuotaService $quota): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'uuid'],
            'to' => ['required', 'string', 'max:100'],
            'target_type' => ['nullable', 'in:contact,group'],
            'type' => ['required', 'in:text,image,document,audio,video'],
            'message' => ['required', 'string'],
            'media_url' => ['nullable', 'url', 'max:500'],
            'scheduled_at' => ['nullable', 'date'],
            'recurrence' => ['nullable', 'in:none,daily,weekly,monthly,custom'],
            'recurrence_interval' => ['nullable', 'integer', 'min:1', 'max:365'],
            'recurrence_until' => ['nullable', 'date', 'after:scheduled_at'],
        ]);

        $user = $request->user();
        $sessionExists = WhatsappSession::query()
            ->where('user_id', $user->id)
            ->where('session_id', $validated['session_id'])
            ->where('status', 'connected')
            ->exists();

        if (! $sessionExists) {
            return back()->withErrors(['session_id' => 'Pilih sesi yang sudah connected.'])->withInput();
        }

        if (! $quota->canSendType($user, $validated['type'])) {
            return back()->withErrors(['type' => 'Paket Anda belum mendukung tipe pesan ini.'])->withInput();
        }

        if (! $quota->hasQuota($user)) {
            return back()->withErrors(['message' => 'Kuota pesan habis. Silakan upgrade paket Anda.'])->withInput();
        }

        try {
            $quota->decrementQuota($user);
        } catch (RuntimeException) {
            return back()->withErrors(['message' => 'Kuota pesan habis. Silakan upgrade paket Anda.'])->withInput();
        }

        $status = isset($validated['scheduled_at']) && now()->lt($validated['scheduled_at']) ? 'scheduled' : 'queued';

        $message = Message::query()->create([
            'user_id' => $user->id,
            'session_id' => $validated['session_id'],
            'direction' => 'outbound',
            'target_type' => $validated['target_type'] ?? 'contact',
            'to_number' => $validated['to'],
            'type' => $validated['type'],
            'content' => $quota->applyMessagePolicy($user, $validated['type'], $validated['message']),
            'media_url' => $validated['media_url'] ?? null,
            'status' => $status,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'recurrence' => ($validated['recurrence'] ?? 'none') === 'none' ? null : $validated['recurrence'],
            'recurrence_interval' => $validated['recurrence_interval'] ?? 1,
            'recurrence_until' => $validated['recurrence_until'] ?? null,
        ]);

        MessageLog::query()->create([
            'message_id' => $message->id,
            'event' => $status,
            'description' => $status === 'scheduled' ? 'Message scheduled from CMS.' : 'Message queued from CMS.',
            'created_at' => now(),
        ]);

        if ($status === 'queued') {
            SendWhatsAppMessage::dispatch($message->id);
        }

        return redirect()->route('cms.messages.index')->with('status', 'Pesan masuk queue.');
    }
}
