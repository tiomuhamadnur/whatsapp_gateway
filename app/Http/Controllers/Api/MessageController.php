<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Message;
use App\Models\MessageLog;
use App\Models\WhatsappSession;
use App\Services\QuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Message::query()
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->query('session_id'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate((int) $request->query('per_page', 20)),
        ]);
    }

    public function send(Request $request, QuotaService $quota): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'uuid'],
            'to' => ['nullable', 'string', 'max:255'],
            'targets' => ['nullable', 'array', 'min:1'],
            'targets.*' => ['string', 'max:255'],
            'target_type' => ['nullable', 'in:contact,group,broadcast'],
            'type' => ['nullable', 'in:text,image,document,audio,video'],
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
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp session was not found or is not connected.',
                'code' => 'SESSION_NOT_CONNECTED',
            ], 422);
        }

        $targetType = $validated['target_type'] ?? (isset($validated['targets']) ? 'broadcast' : 'contact');
        $targets = $targetType === 'broadcast' ? ($validated['targets'] ?? []) : [($validated['to'] ?? null)];
        $targets = array_values(array_filter($targets));

        if ($targets === []) {
            return response()->json([
                'success' => false,
                'message' => 'At least one target recipient is required.',
                'code' => 'TARGET_REQUIRED',
            ], 422);
        }

        if ($targetType === 'contact' && count($targets) > 1) {
            return response()->json([
                'success' => false,
                'message' => 'Contact messages can only have one target.',
                'code' => 'INVALID_TARGET_COUNT',
            ], 422);
        }

        $type = $validated['type'] ?? 'text';

        if (! $quota->canSendType($user, $type)) {
            return response()->json([
                'success' => false,
                'message' => 'Your current plan does not support this message type.',
                'code' => 'MESSAGE_TYPE_NOT_ALLOWED',
            ], 422);
        }

        if (in_array($type, ['image', 'document', 'audio', 'video']) && ! $this->isValidMediaUrl($validated['media_url'])) {
            return response()->json([
                'success' => false,
                'message' => 'Media URL is not accessible or invalid. Please use a publicly accessible URL.',
                'code' => 'INVALID_MEDIA_URL',
            ], 422);
        }

        if (! $quota->hasQuota($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Your message quota has been used up. Upgrade your plan to send more messages.',
                'code' => 'QUOTA_EXCEEDED',
            ], 402);
        }

        $created = [];
        $status = $this->scheduledStatus($validated['scheduled_at'] ?? null);

        foreach ($targets as $target) {
            try {
                $quota->decrementQuota($user);
            } catch (RuntimeException) {
                break;
            }

            $message = Message::query()->create([
            'user_id' => $user->id,
            'session_id' => $validated['session_id'],
            'direction' => 'outbound',
                'target_type' => $targetType === 'broadcast' ? $this->inferTargetType($target) : $targetType,
                'to_number' => $target,
                'broadcast_targets' => $targetType === 'broadcast' ? $targets : null,
            'type' => $type,
            'content' => $quota->applyMessagePolicy($user, $type, $validated['message']),
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
                'description' => $status === 'scheduled' ? 'Message scheduled for delivery.' : 'Message queued for delivery.',
                'created_at' => now(),
            ]);

            if ($status === 'queued') {
                SendWhatsAppMessage::dispatch($message->id);
            }

            $created[] = $message->id;
        }

        if ($created === []) {
            return response()->json([
                'success' => false,
                'message' => 'Your message quota is not enough for the broadcast targets.',
                'code' => 'QUOTA_EXCEEDED',
            ], 402);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'message_ids' => $created,
                'status' => $status,
            ],
        ], 202);
    }

    private function scheduledStatus(?string $scheduledAt): string
    {
        return $scheduledAt !== null && now()->lt($scheduledAt) ? 'scheduled' : 'queued';
    }

    private function inferTargetType(string $target): string
    {
        return str_contains($target, '@g.us') ? 'group' : 'contact';
    }
}
