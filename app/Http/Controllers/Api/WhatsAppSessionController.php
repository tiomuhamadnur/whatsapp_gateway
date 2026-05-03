<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappSession;
use App\Services\QuotaService;
use App\Services\WhatsAppNodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class WhatsAppSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sessions = WhatsappSession::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        return response()->json(['success' => true, 'data' => $sessions]);
    }

    public function store(Request $request, QuotaService $quota, WhatsAppNodeService $node): JsonResponse
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
            return response()->json([
                'success' => false,
                'message' => 'Your current plan has reached the maximum number of WhatsApp sessions.',
                'code' => 'SESSION_LIMIT_REACHED',
            ], 422);
        }

        $session = WhatsappSession::query()->create([
            'user_id' => $user->id,
            'session_id' => (string) Str::uuid(),
            'name' => $validated['name'] ?? null,
            'status' => 'connecting',
        ]);

        try {
            $node->connectSession($session->session_id);
        } catch (Throwable $throwable) {
            report($throwable);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->session_id,
                'status' => $session->status,
            ],
        ], 201);
    }

    public function qr(Request $request, string $sessionId): JsonResponse
    {
        $session = $this->ownedSession($request, $sessionId);

        return response()->json([
            'success' => true,
            'data' => [
                'qr' => $session->qr_code,
            ],
        ]);
    }

    public function status(Request $request, string $sessionId, QuotaService $quota): JsonResponse
    {
        $session = $this->ownedSession($request, $sessionId);
        $user = $request->user();
        $subscription = $user->subscription;

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->session_id,
                'status' => $session->status,
                'phone_number' => $session->phone_number,
                'last_active_at' => $session->last_active_at,
                'quota_remaining' => $quota->getRemainingQuota($user),
                'subscription_expires_at' => $subscription?->ends_at,
            ],
        ]);
    }

    public function destroy(Request $request, string $sessionId, WhatsAppNodeService $node): JsonResponse
    {
        $session = $this->ownedSession($request, $sessionId);

        try {
            $node->disconnectSession($session->session_id);
        } catch (Throwable $throwable) {
            report($throwable);
        }

        $session->update(['status' => 'disconnected']);

        return response()->json(['success' => true]);
    }

    public function groups(Request $request, string $sessionId, WhatsAppNodeService $node): JsonResponse
    {
        $session = $this->ownedSession($request, $sessionId);

        return response()->json([
            'success' => true,
            'data' => $node->getGroups($session->session_id),
        ]);
    }

    public function contacts(Request $request, string $sessionId, WhatsAppNodeService $node): JsonResponse
    {
        $session = $this->ownedSession($request, $sessionId);

        return response()->json([
            'success' => true,
            'data' => $node->getContacts($session->session_id),
        ]);
    }

    private function ownedSession(Request $request, string $sessionId): WhatsappSession
    {
        return WhatsappSession::query()
            ->where('user_id', $request->user()->id)
            ->where('session_id', $sessionId)
            ->firstOrFail();
    }
}
