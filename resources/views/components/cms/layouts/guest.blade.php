@props(['title' => 'WA Gateway', 'heading' => null])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'WA Gateway' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 text-white antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-8">
                <div class="text-sm font-medium text-zinc-400">WA Gateway SaaS</div>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $heading ?? $title ?? 'Masuk' }}</h1>
            </div>
            <div class="rounded-lg border border-white/10 bg-white p-6 text-zinc-950 shadow-2xl">
                @if ($errors->any())
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ $errors->first() }}
                    </div>
                @endif

                {{ $slot }}
            </div>
        </div>
    </main>
</body>
</html>
