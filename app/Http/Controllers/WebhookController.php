<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageLog;
use App\Models\WhatsappSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        if (! $this->hasValidSignature($request)) {
            return response()->json(['success' => false, 'message' => 'Invalid signature.'], 401);
        }

        $validated = $request->validate([
            'event' => ['required', 'string'],
            'data' => ['required', 'array'],
        ]);

        match ($validated['event']) {
            'session.qr' => $this->sessionQr($validated['data']),
            'session.update' => $this->sessionUpdate($validated['data']),
            'message.sent' => $this->messageSent($validated['data']),
            'message.failed' => $this->messageFailed($validated['data']),
            'message.received' => $this->messageReceived($validated['data']),
            default => null,
        };

        return response()->json(['success' => true]);
    }

    private function hasValidSignature(Request $request): bool
    {
        $secret = (string) config('services.node_wa.webhook_secret');

        if ($secret === '') {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);
        $actual = (string) $request->header('X-Webhook-Signature', '');

        return hash_equals($expected, $actual);
    }

    private function sessionQr(array $data): void
    {
        WhatsappSession::query()
            ->where('session_id', $data['session_id'] ?? null)
            ->update([
                'status' => 'qr_ready',
                'qr_code' => $data['qr'] ?? null,
            ]);
    }

    private function sessionUpdate(array $data): void
    {
        WhatsappSession::query()
            ->where('session_id', $data['session_id'] ?? null)
            ->update([
                'status' => $data['status'] ?? 'disconnected',
                'phone_number' => $data['phone_number'] ?? null,
                'qr_code' => null,
                'last_active_at' => now(),
            ]);
    }

    private function messageSent(array $data): void
    {
        $message = Message::query()->find($data['message_id'] ?? null);

        if ($message === null) {
            return;
        }

        $message->update([
            'status' => $data['status'] ?? 'sent',
            'wa_message_id' => $data['wa_message_id'] ?? null,
            'sent_at' => isset($data['sent_at']) ? Carbon::parse($data['sent_at']) : now(),
        ]);

        $this->log($message, 'sent', 'Message sent successfully.', $data);
    }

    private function messageFailed(array $data): void
    {
        $message = Message::query()->find($data['message_id'] ?? null);

        if ($message === null) {
            return;
        }

        $message->update([
            'status' => 'failed',
            'error_message' => $data['error'] ?? 'Unknown error.',
        ]);

        $this->log($message, 'failed', $message->error_message, $data);
    }

    private function messageReceived(array $data): void
    {
        $session = WhatsappSession::query()
            ->where('session_id', $data['session_id'] ?? null)
            ->first();

        if ($session === null) {
            return;
        }

        $message = Message::query()->create([
            'user_id' => $session->user_id,
            'session_id' => $session->session_id,
            'direction' => 'inbound',
            'from_number' => $data['from'] ?? null,
            'type' => $data['type'] ?? 'text',
            'content' => $data['message'] ?? '',
            'status' => 'received',
            'sent_at' => isset($data['timestamp']) ? Carbon::createFromTimestamp($data['timestamp']) : now(),
        ]);

        $this->log($message, 'received', 'Inbound message received.', $data);
    }

    private function log(Message $message, string $event, ?string $description, array $meta): void
    {
        MessageLog::query()->create([
            'message_id' => $message->id,
            'event' => $event,
            'description' => $description,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}
