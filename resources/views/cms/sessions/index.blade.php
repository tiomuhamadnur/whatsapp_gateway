<x-cms.layouts.app title="Sessions" heading="WhatsApp Sessions" eyebrow="Koneksi">
    @if (auth()->user()->isPrivileged())
        <div class="mb-5 rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
            Akun Anda role <strong>{{ auth()->user()->role }}</strong>. Limit jumlah session dan kuota pesan tidak berlaku.
        </div>
    @endif

    <section class="rounded-lg border border-zinc-200 bg-white p-4">
        <form method="POST" action="{{ route('cms.sessions.store') }}" class="grid gap-3 sm:grid-cols-[1fr_auto]">
            @csrf
            <label>
                <span class="text-sm font-medium">Nama sesi</span>
                <input name="name" value="{{ old('name') }}" placeholder="Toko Online" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
            </label>
            <div class="flex items-end">
                <button class="w-full rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Buat Sesi</button>
            </div>
        </form>
    </section>

    <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($sessions as $session)
            <article class="rounded-lg border border-zinc-200 bg-white p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold">{{ $session->name ?: 'Untitled Session' }}</h2>
                        <p class="mt-1 break-all text-xs text-zinc-500">{{ $session->session_id }}</p>
                    </div>
                    <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium">{{ $session->status }}</span>
                </div>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">Phone</dt>
                        <dd class="font-medium">{{ $session->phone_number ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-500">Last active</dt>
                        <dd class="font-medium">{{ $session->last_active_at?->diffForHumans() ?: '-' }}</dd>
                    </div>
                </dl>

                @if ($session->qr_code)
                    <div class="mt-4 rounded-md border border-zinc-200 bg-zinc-50 p-3">
                        <img src="{{ $session->qr_code }}" alt="QR WhatsApp session" class="mx-auto h-48 w-48">
                    </div>
                @endif

                <div class="mt-4 space-y-3 border-t border-zinc-100 pt-4">
                    <button type="button" data-modal-open="session-edit-{{ $session->id }}" class="w-full rounded-md bg-zinc-950 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Edit Session</button>
                    <x-cms.modal id="session-edit-{{ $session->id }}" title="Edit Session">
                        <form method="POST" action="{{ route('cms.sessions.update', $session->session_id) }}" class="grid gap-4">
                            @csrf
                            @method('PATCH')
                            <label>
                                <span class="text-sm font-medium">Nama session</span>
                                <input name="name" value="{{ old('name', $session->name) }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                            </label>
                            <button class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Simpan</button>
                        </form>
                    </x-cms.modal>

                    <div class="grid gap-2 sm:grid-cols-2">
                        <a href="{{ route('cms.sessions.groups', $session->session_id) }}" class="rounded-md border border-zinc-300 px-3 py-2 text-center text-sm font-medium hover:bg-zinc-100">Groups</a>
                        <a href="{{ route('cms.sessions.contacts', $session->session_id) }}" class="rounded-md border border-zinc-300 px-3 py-2 text-center text-sm font-medium hover:bg-zinc-100">Contacts</a>
                        <form method="POST" action="{{ route('cms.sessions.disconnect', $session->session_id) }}">
                            @csrf
                            <button class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100">Disconnect</button>
                        </form>

                        <form method="POST" action="{{ route('cms.sessions.destroy', $session->session_id) }}" onsubmit="return confirm('Hapus session ini permanen?')">
                            @csrf
                            @method('DELETE')
                            <button class="w-full rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Delete</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-zinc-300 bg-white p-8 text-sm text-zinc-500 md:col-span-2 xl:col-span-3">
                Belum ada sesi. Buat sesi baru untuk menampilkan QR.
            </div>
        @endforelse
    </section>

    <div class="mt-6">{{ $sessions->links() }}</div>
</x-cms.layouts.app>
