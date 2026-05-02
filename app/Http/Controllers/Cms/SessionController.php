<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\WhatsappSession;
use App\Services\QuotaService;
use App\Services\WhatsAppNodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class SessionController extends Controller
{
    public function index(Request $request): View
    {
        return view('cms.sessions.index', [
            'sessions' => WhatsappSession::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate(12),
        ]);
    }

    public function update(Request $request, string $sessionId): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $session = $this->ownedSession($request, $sessionId);

        $session->update([
            'name' => $validated['name'] ?? null,
        ]);

        return back()->with('status', 'Session berhasil diubah.');
    }

    public function store(Request $request, QuotaService $quota, WhatsAppNodeService $node): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $currentSessions = WhatsappSession::query()
            ->where('user_id', $user->id)
            ->whereNot('status', 'disconnected')
            ->count();

        if ($currentSessions >= $quota->getMaxSessions($user)) {
            return back()->withErrors(['name' => 'Batas jumlah sesi WhatsApp untuk paket Anda sudah tercapai.']);
        }

        $session = WhatsappSession::query()->create([
            'user_id' => $user->id,
            'session_id' => (string) Str::uuid(),
            'name' => $validated['name'] ?? null,
            'status' => 'connecting',
        ]);

        try {
            $node->connectSession($session->session_id);

            return redirect()
                ->route('cms.sessions.index')
                ->with('status', 'Sesi dibuat. Tunggu QR muncul lalu scan dari WhatsApp.');
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()
                ->route('cms.sessions.index')
                ->with('status', 'Sesi dibuat, tapi Node WA belum bisa dihubungi. Jalankan node-wa lalu coba refresh.');
        }
    }

    public function disconnect(Request $request, string $sessionId, WhatsAppNodeService $node): RedirectResponse
    {
        $session = $this->ownedSession($request, $sessionId);

        try {
            $node->disconnectSession($session->session_id);
        } catch (Throwable $throwable) {
            report($throwable);
        }

        $session->update(['status' => 'disconnected']);

        return back()->with('status', 'Sesi WhatsApp diputus.');
    }

    public function destroy(Request $request, string $sessionId, WhatsAppNodeService $node): RedirectResponse
    {
        $session = $this->ownedSession($request, $sessionId);

        try {
            $node->disconnectSession($session->session_id);
        } catch (Throwable $throwable) {
            report($throwable);
        }

        $session->delete();

        return back()->with('status', 'Session berhasil dihapus permanen.');
    }

    private function ownedSession(Request $request, string $sessionId): WhatsappSession
    {
        return WhatsappSession::query()
            ->where('user_id', $request->user()->id)
            ->where('session_id', $sessionId)
            ->firstOrFail();
    }
}
