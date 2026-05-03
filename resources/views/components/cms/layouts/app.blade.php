@props(['title' => 'WA Gateway CMS', 'heading' => null, 'eyebrow' => 'CMS'])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'WA Gateway CMS' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-950 antialiased">
    <div class="min-h-screen lg:flex">
        <aside data-sidebar class="sidebar-shell fixed inset-y-0 left-0 z-40 w-72 -translate-x-full border-r border-zinc-200 bg-white/95 backdrop-blur transition-transform lg:translate-x-0 lg:w-64">
            <div class="flex h-16 items-center justify-between px-5 lg:h-20">
                <a href="{{ route('cms.dashboard') }}">
                    <x-cms.logo />
                </a>
                <button type="button" data-sidebar-close class="rounded-md border border-zinc-300 px-2 py-1 text-sm lg:hidden" aria-label="Close sidebar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <nav class="space-y-1 overflow-y-auto px-3 pb-3">
                @php
                    $items = [
                        ['Dashboard', 'cms.dashboard', 'fa-chart-line'],
                        ['Sessions', 'cms.sessions.index', 'fa-mobile-screen-button'],
                        ['Messages', 'cms.messages.index', 'fa-comments'],
                        ['API Tokens', 'cms.tokens.index', 'fa-key'],
                        ['API Docs', 'cms.docs.api', 'fa-book'],
                    ];
                    $ownerItems = [
                        ['Owner Home', 'owner.dashboard', 'fa-gauge-high'],
                        ['All Users', 'owner.users.index', 'fa-users'],
                        ['Product Plans', 'owner.plans.index', 'fa-layer-group'],
                        ['All Sessions', 'owner.sessions.index', 'fa-signal'],
                        ['All Messages', 'owner.messages.index', 'fa-inbox'],
                    ];
                @endphp
                @foreach ($items as [$label, $route, $icon])
                    <a href="{{ route($route) }}" data-sidebar-link class="flex items-center whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs($route) ? 'bg-zinc-950 text-white' : 'text-zinc-700 hover:bg-zinc-100' }}">
                        <span class="inline-flex h-6 w-6 items-center justify-center"><i class="fa-solid {{ $icon }}"></i></span>
                        <span class="sidebar-label ml-2">{{ $label }}</span>
                    </a>
                @endforeach
                @if (auth()->user()->isPrivileged())
                    <div class="hidden px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wide text-zinc-400 lg:block">Owner</div>
                    @foreach ($ownerItems as [$label, $route, $icon])
                        <a href="{{ route($route) }}" data-sidebar-link class="flex items-center whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs($route) ? 'bg-zinc-950 text-white' : 'text-zinc-700 hover:bg-zinc-100' }}">
                            <span class="inline-flex h-6 w-6 items-center justify-center"><i class="fa-solid {{ $icon }}"></i></span>
                            <span class="sidebar-label ml-2">{{ $label }}</span>
                        </a>
                    @endforeach
                @endif
            </nav>
        </aside>
        <div data-sidebar-backdrop class="fixed inset-0 z-30 hidden bg-zinc-950/40 lg:hidden"></div>

        <main class="content-shell lg:ml-64 lg:flex-1">
            <header class="sticky top-0 z-20 border-b border-zinc-200 bg-white/90 backdrop-blur">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button type="button" data-sidebar-toggle class="rounded-md border border-zinc-300 px-2 py-1.5 text-sm" aria-label="Toggle sidebar">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div>
                            <p class="text-xs font-medium text-zinc-500">{{ $eyebrow ?? 'CMS' }}</p>
                            <h1 class="text-xl font-semibold tracking-tight">{{ $heading ?? $title ?? 'WA Gateway' }}</h1>
                        </div>
                    </div>
                    @php($activeSubscription = auth()->user()->subscriptions()->with('productPlan')->where('is_active', true)->latest('ends_at')->first())
                    <div class="flex items-center gap-3 text-sm">
                        <div class="hidden text-right sm:block">
                            <div class="font-medium">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-zinc-500">
                                {{ $activeSubscription?->productPlan?->name ?: 'No plan' }}
                                @if ($activeSubscription?->ends_at)
                                    - exp {{ $activeSubscription->ends_at->format('Y-m-d') }}
                                @endif
                            </div>
                        </div>
                        <form method="POST" action="{{ route('cms.logout') }}">
                            @csrf
                            <button data-confirm="Sign out of this account?" class="rounded-md bg-zinc-950 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800">
                                <i class="fa-solid fa-right-from-bracket sm:mr-1"></i><span class="hidden sm:inline">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <div class="font-medium">Some fields need your attention.</div>
                        <ul class="mt-2 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>
    <dialog id="confirm-action-modal" class="w-[min(420px,calc(100vw-2rem))] rounded-lg border border-zinc-200 p-0 shadow-2xl backdrop:bg-zinc-950/40">
        <div class="p-5">
            <h2 class="text-base font-semibold">Confirm Action</h2>
            <p id="confirm-action-message" class="mt-2 text-sm text-zinc-600">Are you sure you want to continue?</p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" data-confirm-cancel class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100">Cancel</button>
                <button type="button" data-confirm-ok class="rounded-md bg-zinc-950 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Continue</button>
            </div>
        </div>
    </dialog>
    @livewireScripts
</body>
</html>
