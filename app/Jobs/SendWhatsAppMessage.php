<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\MessageLog;
use App\Models\WhatsappSession;
use App\Services\WhatsAppNodeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(public int $messageId)
    {
        $this->onQueue('default');
    }

    public function handle(WhatsAppNodeService $node): void
    {
        $message = Message::query()->findOrFail($this->messageId);
        $session = WhatsappSession::query()
            ->where('session_id', $message->session_id)
            ->where('user_id', $message->user_id)
            ->first();

        if ($session === null || $session->status !== 'connected') {
            $this->markFailed($message, 'Session is not connected.');

            return;
        }

        $message->update(['status' => 'sending']);
        $this->log($message, 'sending', 'Message handed to WhatsApp node service.');

        \Log::info("Sending message ID {$message->id} to {$message->to_number}");

        try {
            $response = $node->sendMessage(array_merge([
                'message_id' => $message->id,
                'session_id' => $message->session_id,
                'to' => $message->to_number,
                'target_type' => $message->target_type,
                'type' => $message->type,
                'message' => $message->content,
                'media_url' => $message->media_url,
            ], $message->payload ?? []));

            $message->update([
                'status' => $response['data']['status'] ?? 'sending',
                'wa_message_id' => $response['data']['wa_message_id'] ?? null,
            ]);

            $this->log($message, 'node.accepted', 'Node service accepted the message.', $response);
        } catch (Throwable $throwable) {
            $message->increment('retry_count');

            throw $throwable;
        }
    }

    public function failed(Throwable $throwable): void
    {
        $message = Message::query()->find($this->messageId);

        if ($message !== null) {
            $this->markFailed($message, $throwable->getMessage());
        }
    }

    private function markFailed(Message $message, string $reason): void
    {
        $message->update([
            'status' => 'failed',
            'error_message' => $reason,
        ]);

        $this->log($message, 'failed', $reason);
    }

    private function log(Message $message, string $event, ?string $description = null, ?array $meta = null): void
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
