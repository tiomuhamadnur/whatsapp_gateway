<x-cms.layouts.app title="Owner Dashboard" heading="Owner Dashboard" eyebrow="Platform">
    @php
        $planTotal = max(1, $planDistribution->sum());
        $stats = [
            ['Users', $userCount, 'fa-users', 'Registered accounts'],
            ['Active Subscriptions', $activeSubscriptionCount, 'fa-credit-card', 'Currently billable'],
            ['Connected Devices', $connectedSessionCount.'/'.$sessionCount, 'fa-signal', 'Online sessions'],
            ['Messages Today', $sentTodayCount, 'fa-calendar-day', 'Created today'],
            ['Total Messages', $messageCount, 'fa-comments', 'All retained history'],
            ['Active Plans', $planCount, 'fa-layer-group', 'Available products'],
        ];
    @endphp

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($stats as [$label, $value, $icon, $caption])
            <article class="ui-card p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-medium text-[color:var(--muted-foreground)]">{{ $label }}</p>
                    <span class="ui-icon"><i class="fa-solid {{ $icon }}"></i></span>
                </div>
                <div class="mt-3 truncate text-3xl font-semibold tracking-tight">{{ is_numeric($value) ? number_format($value) : $value }}</div>
                <p class="mt-1 text-xs text-[color:var(--muted-foreground)]">{{ $caption }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
        <article class="ui-card p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold">Platform Message Trend</h2>
                    <p class="mt-1 text-sm text-[color:var(--muted-foreground)]">Total retained message records.</p>
                </div>
                <select id="owner-days-filter" class="rounded-md border border-zinc-300 px-2 py-1.5 text-xs outline-none focus:border-zinc-950">
                    <option value="7">Last 7 days</option>
                    <option value="14">Last 14 days</option>
                    <option value="30" selected>Last 30 days</option>
                </select>
            </div>
            <div id="owner-message-chart" style="height: 300px;"></div>
        </article>

        <article class="ui-card p-5">
            <h2 class="text-base font-semibold">Subscription Mix</h2>
            <p class="mt-1 text-sm text-[color:var(--muted-foreground)]">Active subscription distribution by plan.</p>
            <div class="mt-6 space-y-4">
                @forelse ($planDistribution as $plan => $count)
                    @php($percent = round(($count / $planTotal) * 100))
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span>{{ $plan }}</span>
                            <span class="text-[color:var(--muted-foreground)]">{{ number_format($count) }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-[color:var(--muted)]">
                            <div class="h-full rounded-full bg-[color:var(--primary)]" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-md border border-dashed border-[color:var(--border)] p-4 text-sm text-[color:var(--muted-foreground)]">No active subscriptions yet.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
        <article class="ui-card overflow-hidden">
            <div class="border-b border-[color:var(--border)] px-5 py-4">
                <h2 class="font-semibold">New Users</h2>
            </div>
            <div class="divide-y divide-[color:var(--border)]">
                @foreach ($recentUsers as $user)
                    <div class="flex items-center justify-between gap-4 px-5 py-4 text-sm">
                        <div class="min-w-0">
                            <div class="font-medium">{{ $user->name }}</div>
                            <div class="wrap-anywhere text-[color:var(--muted-foreground)]">{{ $user->email }}</div>
                        </div>
                        <span class="ui-badge">{{ $user->role }}</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="ui-card overflow-hidden">
            <div class="border-b border-[color:var(--border)] px-5 py-4">
                <h2 class="font-semibold">Recent Chats</h2>
            </div>
            <div class="divide-y divide-[color:var(--border)]">
                @foreach ($recentMessages as $message)
                    <div class="px-5 py-4 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <div class="wrap-anywhere font-medium">{{ $message->user?->email }}</div>
                            <span class="ui-badge">{{ $message->status }}</span>
                        </div>
                        <div class="wrap-anywhere mt-2 text-[color:var(--muted-foreground)]">{{ $message->content }}</div>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/responsive.js"></script>
    <script>
        const ownerChartData = @json($messageTrend);
        
        function renderOwnerChart(days = 30) {
            const filtered = ownerChartData.slice(-days);
            const categories = filtered.map(d => d.label);
            const data = filtered.map(d => d.value);
            
            Highcharts.chart('owner-message-chart', {
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
                    name: 'Platform Messages',
                    data: data,
                    dataLabels: { enabled: true, format: '{y}' }
                }],
                credits: { enabled: false },
                tooltip: { shared: true, crosshairs: true }
            });
        }

        renderOwnerChart(30);

        document.getElementById('owner-days-filter').addEventListener('change', (e) => {
            renderOwnerChart(parseInt(e.target.value));
        });
    </script>
</x-cms.layouts.app>
