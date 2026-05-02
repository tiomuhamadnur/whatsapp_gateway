<x-cms.layouts.app title="All Users" heading="All Users" eyebrow="Owner">
    @if (session('owner_plain_text_token'))
        <section class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
            <h2 class="font-semibold text-amber-950">Token baru untuk {{ session('owner_token_user') }}</h2>
            <p class="mt-1 text-sm text-amber-800">Copy token ini sekarang. Token tidak akan ditampilkan lagi.</p>
            <pre class="mt-3 overflow-x-auto rounded-md bg-white p-3 text-sm text-zinc-950">{{ session('owner_plain_text_token') }}</pre>
        </section>
    @endif

    <x-cms.data-table search="Cari user, email, role, paket..." per-page="10">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Price / Period</th>
                    <th class="px-4 py-3">Usage</th>
                    <th class="px-4 py-3">Sessions</th>
                    <th class="px-4 py-3">Messages</th>
                    <th class="px-4 py-3">Tokens</th>
                    <th class="px-4 py-3">Update</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach ($users as $user)
                    @php($subscription = $user->subscriptions->firstWhere('is_active', true))
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $user->name }}</div>
                            <div class="text-zinc-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $user->role }}</td>
                        <td class="px-4 py-3">{{ $subscription?->productPlan?->name ?: $subscription?->plan_name ?: '-' }}</td>
                        <td class="px-4 py-3">
                            <div>{{ $subscription?->currency ?? 'IDR' }} {{ number_format($subscription?->price ?? 0, 0, ',', '.') }}</div>
                            <div class="text-xs text-zinc-500">{{ $subscription?->starts_at?->format('Y-m-d') ?: '-' }} - {{ $subscription?->ends_at?->format('Y-m-d') ?: '-' }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $subscription?->messages_used_today ?? 0 }} / {{ $subscription?->productPlan?->daily_message_quota ?? $subscription?->message_quota ?? 0 }}</td>
                        <td class="px-4 py-3">{{ $user->whatsapp_sessions_count }}</td>
                        <td class="px-4 py-3">{{ $user->messages_count }}</td>
                        <td class="px-4 py-3">
                            <div>{{ $user->tokens_count }}</div>
                            <form method="POST" action="{{ route('owner.users.tokens.store', $user) }}" class="mt-2">
                                @csrf
                                <button class="rounded-md border border-zinc-300 px-2 py-1 text-xs font-medium hover:bg-zinc-100">Issue Token</button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <button type="button" data-modal-open="user-edit-{{ $user->id }}" class="rounded-md bg-zinc-950 px-3 py-1.5 text-sm font-semibold text-white">Edit</button>
                            <x-cms.modal id="user-edit-{{ $user->id }}" title="Edit User: {{ $user->email }}">
                                <form method="POST" action="{{ route('owner.users.update', $user) }}" class="grid gap-4">
                                    @csrf
                                    @method('PATCH')
                                    <label>
                                        <span class="text-sm font-medium">Role</span>
                                        <select name="role" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
                                            @foreach (['client', 'admin', 'superadmin'] as $role)
                                                <option value="{{ $role }}" @selected($user->role === $role)>{{ $role }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>
                                        <span class="text-sm font-medium">Paket</span>
                                        <select name="product_plan_id" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
                                            <option value="">Pilih paket</option>
                                            @foreach ($plans as $plan)
                                                <option value="{{ $plan->id }}" @selected($subscription?->product_plan_id === $plan->id)>{{ $plan->name }} - {{ $plan->currency }} {{ number_format($plan->price, 0, ',', '.') }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>
                                        <span class="text-sm font-medium">Tanggal expired</span>
                                        <input type="date" name="ends_at" value="{{ $subscription?->ends_at?->format('Y-m-d') }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
                                    </label>
                                    <button class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white">Simpan Perubahan</button>
                                </form>
                            </x-cms.modal>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-cms.data-table>
</x-cms.layouts.app>
