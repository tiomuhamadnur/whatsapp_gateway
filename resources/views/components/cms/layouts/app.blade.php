@props(['title' => 'WA Gateway CMS', 'heading' => null, 'eyebrow' => 'CMS'])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'WA Gateway CMS' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-950 antialiased">
    <div class="min-h-screen lg:flex">
        <aside class="sidebar-shell border-b border-zinc-200 bg-white/95 backdrop-blur lg:fixed lg:inset-y-0 lg:left-0 lg:w-64 lg:border-b-0 lg:border-r">
            <div class="flex h-16 items-center justify-between px-5 lg:h-20">
                <a href="{{ route('cms.dashboard') }}">
                    <x-cms.logo />
                </a>
                <form method="POST" action="{{ route('cms.logout') }}" class="lg:hidden">
                    @csrf
                    <button class="text-sm text-zinc-600">Logout</button>
                </form>
            </div>
            <nav class="flex gap-1 overflow-x-auto px-3 pb-3 lg:block lg:space-y-1 lg:overflow-visible">
                @php
                    $items = [
                        ['Dashboard', 'cms.dashboard'],
                        ['Sessions', 'cms.sessions.index'],
                        ['Messages', 'cms.messages.index'],
                        ['API Tokens', 'cms.tokens.index'],
                        ['API Docs', 'cms.docs.api'],
                    ];
                    $ownerItems = [
                        ['Owner Home', 'owner.dashboard'],
                        ['All Users', 'owner.users.index'],
                        ['Product Plans', 'owner.plans.index'],
                        ['All Sessions', 'owner.sessions.index'],
                        ['All Messages', 'owner.messages.index'],
                    ];
                @endphp
                @foreach ($items as [$label, $route])
                    <a href="{{ route($route) }}" class="block whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs($route) ? 'bg-zinc-950 text-white' : 'text-zinc-700 hover:bg-zinc-100' }}">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-zinc-100 text-xs text-zinc-700">{{ substr($label, 0, 1) }}</span>
                        <span class="sidebar-label ml-2">{{ $label }}</span>
                    </a>
                @endforeach
                @if (auth()->user()->isPrivileged())
                    <div class="hidden px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wide text-zinc-400 lg:block">Owner</div>
                    @foreach ($ownerItems as [$label, $route])
                        <a href="{{ route($route) }}" class="block whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs($route) ? 'bg-zinc-950 text-white' : 'text-zinc-700 hover:bg-zinc-100' }}">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-zinc-100 text-xs text-zinc-700">{{ substr($label, 0, 1) }}</span>
                            <span class="sidebar-label ml-2">{{ $label }}</span>
                        </a>
                    @endforeach
                @endif
            </nav>
        </aside>

        <main class="content-shell lg:ml-64 lg:flex-1">
            <header class="sticky top-0 z-20 border-b border-zinc-200 bg-white/90 backdrop-blur">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button type="button" data-sidebar-toggle class="hidden rounded-md border border-zinc-300 px-2 py-1.5 text-sm lg:block">Menu</button>
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
                            <button class="rounded-md bg-zinc-950 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Logout</button>
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
                        <div class="font-medium">Ada input yang perlu diperbaiki.</div>
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
</body>
</html>
