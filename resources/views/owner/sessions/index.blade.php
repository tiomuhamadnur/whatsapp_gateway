<x-cms.layouts.app title="All Sessions" heading="All Sessions & Devices" eyebrow="Owner">
    <x-cms.data-table search="Search user, device, status, or phone..." per-page="12">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Session</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Last Active</th>
                    <th class="px-4 py-3">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach ($sessions as $session)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $session->user?->name }}</div>
                            <div class="text-zinc-500">{{ $session->user?->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $session->name ?: 'Untitled' }}</div>
                            <div class="wrap-anywhere text-xs text-zinc-500">{{ $session->session_id }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $session->phone_number ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $session->status }}</td>
                        <td class="px-4 py-3">{{ $session->last_active_at?->format('Y-m-d H:i') ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $session->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-cms.data-table>
</x-cms.layouts.app>
