<x-cms.layouts.app title="Messages" heading="Messages" eyebrow="Kirim & Riwayat">
    <section class="rounded-lg border border-zinc-200 bg-white p-4">
        <form method="POST" action="{{ route('cms.messages.store') }}" class="grid gap-4 lg:grid-cols-2">
            @csrf
            <label>
                <span class="text-sm font-medium">Session</span>
                <select name="session_id" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                    <option value="">Pilih connected session</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session->session_id }}" @selected(old('session_id') === $session->session_id)>
                            {{ $session->name ?: $session->session_id }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="text-sm font-medium">Target type</span>
                <select name="target_type" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                    <option value="contact" @selected(old('target_type', 'contact') === 'contact')>Contact</option>
                    <option value="group" @selected(old('target_type') === 'group')>Group</option>
                </select>
            </label>
            <label>
                <span class="text-sm font-medium">Nomor / Group ID</span>
                <input name="to" value="{{ old('to') }}" placeholder="6281234567890 atau 1203xxxx@g.us" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
            </label>
            <label>
                <span class="text-sm font-medium">Type</span>
                <select name="type" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                    @foreach (['text', 'image', 'document', 'audio', 'video'] as $type)
                        <option value="{{ $type }}" @selected(old('type', 'text') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="text-sm font-medium">Media URL</span>
                <input name="media_url" value="{{ old('media_url') }}" placeholder="https://example.com/file.jpg" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
            </label>
            <label class="lg:col-span-2">
                <span class="text-sm font-medium">Message</span>
                <textarea name="message" rows="4" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">{{ old('message') }}</textarea>
            </label>
            <label>
                <span class="text-sm font-medium">Jadwalkan</span>
                <input name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at') }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
            </label>
            <label>
                <span class="text-sm font-medium">Repeat</span>
                <select name="recurrence" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                    @foreach (['none', 'daily', 'weekly', 'monthly', 'custom'] as $recurrence)
                        <option value="{{ $recurrence }}" @selected(old('recurrence', 'none') === $recurrence)>{{ $recurrence }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="text-sm font-medium">Interval repeat</span>
                <input name="recurrence_interval" type="number" min="1" value="{{ old('recurrence_interval', 1) }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
            </label>
            <label>
                <span class="text-sm font-medium">Repeat sampai</span>
                <input name="recurrence_until" type="datetime-local" value="{{ old('recurrence_until') }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
            </label>
            <div class="lg:col-span-2">
                <button class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Kirim Pesan</button>
            </div>
        </form>
    </section>

    <section class="mt-6">
        <h2 class="mb-3 font-semibold">History</h2>
        <x-cms.data-table search="Cari pesan, nomor, status..." per-page="10">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Direction</th>
                        <th class="px-4 py-3">Target</th>
                        <th class="px-4 py-3">Message</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($messages as $message)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $message->id }}</td>
                            <td class="px-4 py-3">{{ $message->direction }}</td>
                            <td class="px-4 py-3">{{ $message->to_number ?: $message->from_number }}</td>
                            <td class="max-w-sm truncate px-4 py-3">{{ $message->content }}</td>
                            <td class="px-4 py-3">{{ $message->status }}</td>
                            <td class="px-4 py-3 text-zinc-500">{{ $message->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">Belum ada pesan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-cms.data-table>
    </section>

    <div class="mt-6">{{ $messages->links() }}</div>
</x-cms.layouts.app>
