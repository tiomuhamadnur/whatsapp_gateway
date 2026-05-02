<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Message;
use App\Models\MessageLog;
use Illuminate\Console\Command;

class DispatchScheduledMessages extends Command
{
    protected $signature = 'messages:dispatch-scheduled';

    protected $description = 'Dispatch due scheduled WhatsApp messages and prepare recurring occurrences.';

    public function handle(): int
    {
        Message::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->chunkById(100, function ($messages): void {
                foreach ($messages as $message) {
                    $message->update(['status' => 'queued']);

                    MessageLog::query()->create([
                        'message_id' => $message->id,
                        'event' => 'queued',
                        'description' => 'Scheduled message reached due time.',
                        'created_at' => now(),
                    ]);

                    SendWhatsAppMessage::dispatch($message->id);
                    $this->createNextOccurrence($message);
                }
            });

        return self::SUCCESS;
    }

    private function createNextOccurrence(Message $message): void
    {
        if ($message->recurrence === null) {
            return;
        }

        $nextAt = match ($message->recurrence) {
            'daily' => $message->scheduled_at?->copy()->addDays($message->recurrence_interval),
            'weekly' => $message->scheduled_at?->copy()->addWeeks($message->recurrence_interval),
            'monthly' => $message->scheduled_at?->copy()->addMonths($message->recurrence_interval),
            'custom' => $message->scheduled_at?->copy()->addDays($message->recurrence_interval),
            default => null,
        };

        if ($nextAt === null || ($message->recurrence_until !== null && $nextAt->gt($message->recurrence_until))) {
            return;
        }

        $next = $message->replicate([
            'status',
            'wa_message_id',
            'error_message',
            'retry_count',
            'sent_at',
            'created_at',
            'updated_at',
        ]);
        $next->status = 'scheduled';
        $next->scheduled_at = $nextAt;
        $next->parent_message_id = $message->parent_message_id ?: $message->id;
        $next->save();
    }
}
