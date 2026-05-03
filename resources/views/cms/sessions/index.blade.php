<x-cms.layouts.app title="Sessions" heading="WhatsApp Sessions" eyebrow="Connections">
    @if (auth()->user()->isPrivileged())
        <div class="mb-5 rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
            Your account role is <strong>{{ auth()->user()->role }}</strong>. Session limits and message quotas are not applied.
        </div>
    @endif

    <section class="rounded-lg border border-zinc-200 bg-white p-4">
        <form method="POST" action="{{ route('cms.sessions.store') }}" data-confirm="Create a new WhatsApp session?" class="grid gap-3 sm:grid-cols-[1fr_auto]">
            @csrf
            <label>
                <span class="text-sm font-medium">Session display name</span>
                <input name="name" value="{{ old('name') }}" placeholder="Online Store" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
            </label>
            <div class="flex items-end">
                <button class="w-full rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800"><i class="fa-solid fa-plus mr-1"></i>Create Session</button>
            </div>
        </form>
    </section>

    <livewire:sessions.session-cards />
</x-cms.layouts.app>
