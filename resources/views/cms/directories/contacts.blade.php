<x-cms.layouts.app title="Contacts" heading="Contacts" eyebrow="{{ $session->name ?: 'Session' }}">
    @if ($error)
        <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $error }}</div>
    @endif

    <x-cms.data-table search="Search contact ID, number, or name..." per-page="10">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Number</th>
                    <th class="px-4 py-3">Contact ID</th>
                    <th class="px-4 py-3">Source</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($contacts as $contact)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $contact['name'] ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $contact['number'] ?? '-' }}</td>
                        <td class="wrap-anywhere px-4 py-3">{{ $contact['id'] ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $contact['source'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-500">Contacts will appear after the session is connected and processes chats.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-cms.data-table>
</x-cms.layouts.app>
