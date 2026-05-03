<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SapaChat CMS' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-950 antialiased">
    <div class="min-h-screen lg:flex">
        <aside class="border-b border-zinc-200 bg-white lg:fixed lg:inset-y-0 lg:left-0 lg:w-64 lg:border-b-0 lg:border-r">
            <div class="flex h-16 items-center justify-between px-5 lg:h-20">
                <a href="{{ route('cms.dashboard') }}" class="font-semibold tracking-tight">SapaChat</a>
                <form method="POST" action="{{ route('cms.logout') }}" class="lg:hidden">
                    @csrf
                    <button class="rounded-md border border-zinc-300 px-3 py-1 text-sm font-medium text-zinc-700 hover:bg-zinc-100">Logout</button>
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
                @endphp
                @foreach ($items as [$label, $route])
                    <a href="{{ route($route) }}" class="block whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs($route) ? 'bg-zinc-950 text-white' : 'text-zinc-700 hover:bg-zinc-100' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
            <div class="hidden px-5 py-5 lg:block">
                <div class="rounded-md border border-zinc-200 bg-zinc-50 p-3 text-sm">
                    <div class="font-medium">{{ auth()->user()->name }}</div>
                    <div class="mt-1 truncate text-zinc-500">{{ auth()->user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('cms.logout') }}" class="mt-3">
                    @csrf
                    <button class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100">Logout</button>
                </form>
            </div>
        </aside>

        <main class="lg:ml-64 lg:flex-1">
            <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
                <header class="mb-6">
                    <p class="text-sm font-medium text-zinc-500">{{ $eyebrow ?? 'CMS' }}</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $heading ?? $title ?? 'SapaChat' }}</h1>
                </header>

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
