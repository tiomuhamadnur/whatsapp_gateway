<x-cms.layouts.app title="API Tokens" heading="API Tokens" eyebrow="Postman">
    @if ($plainTextToken)
        <section class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
            <h2 class="font-semibold text-amber-950">Token baru</h2>
            <p class="mt-1 text-sm text-amber-800">Copy token ini sekarang. Token tidak akan ditampilkan lagi.</p>
            <pre class="mt-3 overflow-x-auto rounded-md bg-white p-3 text-sm text-zinc-950">{{ $plainTextToken }}</pre>
        </section>
    @endif

    <section class="rounded-lg border border-zinc-200 bg-white p-4">
        <form method="POST" action="{{ route('cms.tokens.store') }}" class="grid gap-3 sm:grid-cols-[1fr_auto]">
            @csrf
            <label>
                <span class="text-sm font-medium">Nama token</span>
                <input name="name" value="{{ old('name', 'Postman') }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
            </label>
            <div class="flex items-end">
                <button class="w-full rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Buat Token</button>
            </div>
        </form>
    </section>

    <section class="mt-6 overflow-hidden rounded-lg border border-zinc-200 bg-white">
        <div class="border-b border-zinc-200 px-4 py-3">
            <h2 class="font-semibold">Token aktif</h2>
        </div>
        <div class="divide-y divide-zinc-100">
            @forelse ($tokens as $token)
                <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                    <div>
                        <div class="font-medium">{{ $token->name }}</div>
                        <div class="mt-1 text-zinc-500">Last used: {{ $token->last_used_at?->diffForHumans() ?: '-' }}</div>
                    </div>
                    <form method="POST" action="{{ route('cms.tokens.destroy', $token->id) }}">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100">Delete</button>
                    </form>
                </div>
            @empty
                <div class="px-4 py-8 text-sm text-zinc-500">Belum ada token.</div>
            @endforelse
        </div>
    </section>
</x-cms.layouts.app>
