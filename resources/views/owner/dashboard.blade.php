<x-cms.layouts.app title="Owner Dashboard" heading="Owner Dashboard" eyebrow="Platform">
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Users', $userCount],
            ['Active Subscriptions', $activeSubscriptionCount],
            ['Connected Devices', $connectedSessionCount.'/'.$sessionCount],
            ['Messages Today', $sentTodayCount],
            ['Total Messages', $messageCount],
            ['Active Plans', $planCount],
        ] as [$label, $value])
            <div class="rounded-lg border border-zinc-200 bg-white p-4">
                <div class="text-sm font-medium text-zinc-500">{{ $label }}</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight">{{ $value }}</div>
            </div>
        @endforeach
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white">
            <div class="border-b border-zinc-200 px-4 py-3">
                <h2 class="font-semibold">User Baru</h2>
            </div>
            <div class="divide-y divide-zinc-100">
                @foreach ($recentUsers as $user)
                    <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                        <div>
                            <div class="font-medium">{{ $user->name }}</div>
                            <div class="text-zinc-500">{{ $user->email }}</div>
                        </div>
                        <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium">{{ $user->role }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white">
            <div class="border-b border-zinc-200 px-4 py-3">
                <h2 class="font-semibold">Chat Terbaru</h2>
            </div>
            <div class="divide-y divide-zinc-100">
                @foreach ($recentMessages as $message)
                    <div class="px-4 py-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <div class="font-medium">{{ $message->user?->email }}</div>
                            <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium">{{ $message->status }}</span>
                        </div>
                        <div class="mt-1 truncate text-zinc-500">{{ $message->content }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-cms.layouts.app>
