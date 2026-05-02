@props(['search' => 'Cari data...', 'perPage' => 10])

<div class="rounded-xl border border-zinc-200 bg-white shadow-sm" data-data-table data-per-page="{{ $perPage }}">
    <div class="border-b border-zinc-200 p-3">
        <input
            type="search"
            placeholder="{{ $search }}"
            data-data-table-search
            class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950 sm:max-w-sm"
        >
        <div data-data-table-filters class="mt-3 grid gap-2 md:grid-cols-3 xl:grid-cols-5"></div>
    </div>
    <div class="overflow-x-auto">
        {{ $slot }}
    </div>
    <div class="flex flex-col gap-3 border-t border-zinc-200 px-3 py-3 text-sm text-zinc-600 sm:flex-row sm:items-center sm:justify-between">
        <div data-data-table-info></div>
        <div class="flex items-center gap-2">
            <button type="button" data-data-table-prev class="rounded-md border border-zinc-300 px-3 py-1.5 font-medium hover:bg-zinc-100">Prev</button>
            <span data-data-table-page class="min-w-12 text-center"></span>
            <button type="button" data-data-table-next class="rounded-md border border-zinc-300 px-3 py-1.5 font-medium hover:bg-zinc-100">Next</button>
        </div>
    </div>
</div>
