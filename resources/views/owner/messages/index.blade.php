<x-cms.layouts.app title="All Messages" heading="All Chat History" eyebrow="Owner">
    <x-cms.data-table search="Search user, number, message, or status..." per-page="15">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Direction</th>
                    <th class="px-4 py-3">Target</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Message</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach ($messages as $message)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ number_format($message->id) }}</td>
                        <td class="px-4 py-3">{{ $message->user?->email }}</td>
                        <td class="px-4 py-3">{{ $message->direction }}</td>
                        <td class="wrap-anywhere px-4 py-3">{{ $message->to_number ?: $message->from_number }}</td>
                        <td class="px-4 py-3">{{ $message->type }}</td>
                        <td class="wrap-anywhere max-w-md px-4 py-3">{{ $message->content }}</td>
                        <td class="px-4 py-3">{{ $message->status }}</td>
                        <td class="px-4 py-3">{{ $message->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-cms.data-table>
</x-cms.layouts.app>
