@props(['id', 'title'])

<dialog id="{{ $id }}" class="w-[min(720px,calc(100vw-2rem))] rounded-xl border border-zinc-200 p-0 shadow-2xl backdrop:bg-zinc-950/40">
    <div class="border-b border-zinc-200 px-5 py-4">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold">{{ $title }}</h2>
            <button type="button" class="rounded-md border border-zinc-300 px-2 py-1 text-sm" data-modal-close="{{ $id }}">Close</button>
        </div>
    </div>
    <div class="max-h-[75vh] overflow-y-auto p-5">
        {{ $slot }}
    </div>
</dialog>
