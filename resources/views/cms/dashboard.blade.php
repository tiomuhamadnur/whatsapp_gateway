<x-cms.layouts.app title="Dashboard" heading="Dashboard" eyebrow="Overview">
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['Sessions', $sessionCount, false],
            ['Connected', $connectedCount, false],
            ['Queued', $queuedCount, false],
            ['Sent', $sentCount, false],
            ['Quota Left', $remainingQuota, true],
        ] as [$label, $value, $useLargeFormat])
            <div class="rounded-lg border border-zinc-200 bg-white p-4">
                <div class="text-sm font-medium text-zinc-500">{{ $label }}</div>
                <div class="mt-2 truncate text-3xl font-semibold tracking-tight">{{ $useLargeFormat ? formatLarge($value) : number_format($value) }}</div>
            </div>
        @endforeach
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white">
            <div class="border-b border-zinc-200 px-4 py-3">
                <h2 class="font-semibold">Recent Sessions</h2>
            </div>
            <div class="divide-y divide-zinc-100">
                @forelse ($recentSessions->take(10) as $session)
                    <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                        <div>
                            <div class="font-medium">{{ $session->name ?: 'Untitled Session' }}</div>
                            <div class="wrap-anywhere mt-1 text-zinc-500">{{ $session->session_id }}</div>
                        </div>
                        <div class="text-right">
                            <span class="rounded-full px-2 py-1 text-xs font-medium {{ statusBadge($session->status) }}">{{ $session->status }}</span>
                            <div class="mt-1 text-xs text-zinc-500">{{ $session->created_at->format('M j, H:i') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-sm text-zinc-500">No WhatsApp sessions yet.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white">
            <div class="border-b border-zinc-200 px-4 py-3">
                <h2 class="font-semibold">Recent Messages</h2>
            </div>
            <div class="divide-y divide-zinc-100">
                @forelse ($recentMessages->take(10) as $message)
                    <div class="px-4 py-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <div class="wrap-anywhere font-medium">{{ $message->to_number ?: $message->from_number }}</div>
                            <div class="text-right">
                                <span class="rounded-full px-2 py-1 text-xs font-medium {{ statusBadge($message->status) }}">{{ $message->status }}</span>
                                <div class="mt-1 text-xs text-zinc-500">{{ $message->created_at->format('M j, H:i') }}</div>
                            </div>
                        </div>
                        <div class="wrap-anywhere mt-1 text-zinc-500">{{ $message->content }}</div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-sm text-zinc-500">No messages yet.</div>
                @endforelse
            </div>
        </div>
    </section>
</x-cms.layouts.app>
