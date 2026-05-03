<x-cms.layouts.app title="Group IDs" heading="Group IDs" eyebrow="{{ $session->name ?: 'Session' }}">
    @if ($error)
        <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $error }}</div>
    @endif

    <x-cms.data-table search="Search group ID or name..." per-page="10">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Group Name</th>
                    <th class="px-4 py-3">Group ID</th>
                    <th class="px-4 py-3">Participants</th>
                    <th class="px-4 py-3">Owner</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($groups as $group)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $group['name'] ?? '-' }}</td>
                        <td class="wrap-anywhere px-4 py-3">{{ $group['group_id'] ?? '-' }}</td>
                        <td class="px-4 py-3">{{ number_format($group['participants_count'] ?? 0) }}</td>
                        <td class="wrap-anywhere px-4 py-3">{{ $group['owner'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-500">No groups yet, or the session is not connected.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-cms.data-table>
</x-cms.layouts.app>
