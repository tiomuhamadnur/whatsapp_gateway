<x-cms.layouts.app title="Dashboard" heading="Dashboard" eyebrow="Overview">
    @php
        $statusTotal = max(1, $statusCounts->sum());
        $quotaPercent = $dailyQuota > 0 ? min(100, round(($usedToday / $dailyQuota) * 100)) : 0;
        $stats = [
            ['Sessions', $sessionCount, 'fa-mobile-screen-button', 'All registered devices'],
            ['Connected', $connectedCount, 'fa-signal', 'Ready to send messages'],
            ['Queued', $queuedCount, 'fa-clock', 'Waiting in queue'],
            ['Sent', $sentCount, 'fa-paper-plane', 'Delivered by worker'],
            ['Quota Left', $remainingQuota, 'fa-battery-three-quarters', 'Available today'],
        ];
        $statusColors = [
            'queued' => '#d97706',
            'scheduled' => '#8b5cf6',
            'sending' => '#0f766e',
            'sent' => '#15803d',
            'failed' => '#b91c1c',
            'received' => '#2563eb',
        ];
    @endphp

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($stats as [$label, $value, $icon, $caption])
            <article class="ui-card p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-medium text-[color:var(--muted-foreground)]">{{ $label }}</p>
                    <span class="ui-icon"><i class="fa-solid {{ $icon }}"></i></span>
                </div>
                <div class="mt-3 truncate text-3xl font-semibold tracking-tight">{{ $label === 'Quota Left' ? formatLarge($value) : number_format($value) }}</div>
                <p class="mt-1 text-xs text-[color:var(--muted-foreground)]">{{ $caption }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_0.8fr]">
        <article class="ui-card p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold">Message Activity</h2>
                    <p class="mt-1 text-sm text-[color:var(--muted-foreground)]">Last 30 days of message records.</p>
                </div>
                <div class="flex gap-2">
                    <select id="days-filter" class="rounded-md border border-zinc-300 px-2 py-1.5 text-xs outline-none focus:border-zinc-950">
                        <option value="7">Last 7 days</option>
                        <option value="14">Last 14 days</option>
                        <option value="30" selected>Last 30 days</option>
                    </select>
                </div>
            </div>
            <div id="message-chart" style="height: 300px;"></div>
        </article>

        <article class="ui-card p-5">
            <h2 class="text-base font-semibold">Quota & Status</h2>
            <p class="mt-1 text-sm text-[color:var(--muted-foreground)]">Daily usage and current message states.</p>
            <div class="mt-5">
                <div class="flex items-center justify-between text-sm">
                    <span>Daily quota used</span>
                    <span class="font-medium">{{ number_format($usedToday) }} / {{ number_format($dailyQuota) }}</span>
                </div>
                <div class="mt-2 h-3 overflow-hidden rounded-full bg-[color:var(--muted)]">
                    <div class="h-full rounded-full bg-[color:var(--primary)]" style="width: {{ $quotaPercent }}%"></div>
                </div>
            </div>
            <div class="mt-6 space-y-3">
                @forelse ($statusCounts as $status => $count)
                    @php($percent = round(($count / $statusTotal) * 100))
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="capitalize">{{ $status }}</span>
                            <span class="text-[color:var(--muted-foreground)]">{{ number_format($count) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-[color:var(--muted)]">
                            <div class="h-full rounded-full" style="width: {{ $percent }}%; background: {{ $statusColors[$status] ?? '#111827' }}"></div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-md border border-dashed border-[color:var(--border)] p-4 text-sm text-[color:var(--muted-foreground)]">No message status data yet.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
        <article class="ui-card overflow-hidden">
            <div class="border-b border-[color:var(--border)] px-5 py-4">
                <h2 class="font-semibold">Recent Sessions</h2>
            </div>
            <div class="divide-y divide-[color:var(--border)]">
                @forelse ($recentSessions->take(10) as $session)
                    <div class="flex items-center justify-between gap-4 px-5 py-4 text-sm">
                        <div class="min-w-0">
                            <div class="font-medium">{{ $session->name ?: 'Untitled Session' }}</div>
                            <div class="wrap-anywhere mt-1 text-xs text-[color:var(--muted-foreground)]">{{ $session->session_id }}</div>
                        </div>
                        <div class="shrink-0 text-right">
                            <span class="ui-badge capitalize">{{ $session->status }}</span>
                            <div class="mt-1 text-xs text-[color:var(--muted-foreground)]">{{ $session->created_at->format('M j, H:i') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-sm text-[color:var(--muted-foreground)]">No WhatsApp sessions yet.</div>
                @endforelse
            </div>
        </article>

        <article class="ui-card overflow-hidden">
            <div class="border-b border-[color:var(--border)] px-5 py-4">
                <h2 class="font-semibold">Recent Messages</h2>
            </div>
            <div class="divide-y divide-[color:var(--border)]">
                @forelse ($recentMessages->take(10) as $message)
                    <div class="px-5 py-4 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <div class="wrap-anywhere font-medium">{{ $message->to_number ?: $message->from_number }}</div>
                            <div class="shrink-0 text-right">
                                <span class="ui-badge capitalize">{{ $message->status }}</span>
                                <div class="mt-1 text-xs text-[color:var(--muted-foreground)]">{{ $message->created_at->format('M j, H:i') }}</div>
                            </div>
                        </div>
                        <div class="wrap-anywhere mt-2 text-[color:var(--muted-foreground)]">{{ $message->content }}</div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-sm text-[color:var(--muted-foreground)]">No messages yet.</div>
                @endforelse
            </div>
        </article>
    </section>

    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/responsive.js"></script>
    <script>
        const chartData = @json($messageTrend);
        
        function renderChart(days = 30) {
            const filtered = chartData.slice(-days);
            const categories = filtered.map(d => d.label);
            const data = filtered.map(d => d.value);
            
            Highcharts.chart('message-chart', {
                chart: { type: 'column', backgroundColor: 'transparent' },
                title: null,
                legend: { enabled: false },
                xAxis: {
                    categories: categories,
                    labels: { style: { fontSize: '12px' } }
                },
                yAxis: {
                    title: null,
                    labels: { style: { fontSize: '12px' } }
                },
                plotOptions: {
                    column: { color: '#2f241c', borderRadius: 4 }
                },
                series: [{
                    name: 'Messages',
                    data: data,
                    dataLabels: { enabled: true, format: '{y}' }
                }],
                credits: { enabled: false },
                tooltip: { shared: true, crosshairs: true }
            });
        }

        renderChart(30);

        document.getElementById('days-filter').addEventListener('change', (e) => {
            renderChart(parseInt(e.target.value));
        });
    </script>
</x-cms.layouts.app>
