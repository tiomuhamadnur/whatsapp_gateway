<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;

class PruneMessageHistory extends Command
{
    protected $signature = 'messages:prune-history {--days=60 : Maximum number of days to keep message history}';

    protected $description = 'Delete message history older than the configured retention window.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $deleted = Message::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$deleted} messages older than {$days} days.");

        return self::SUCCESS;
    }
}
