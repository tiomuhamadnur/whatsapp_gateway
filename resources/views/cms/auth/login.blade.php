<x-cms.layouts.guest title="Login" heading="Masuk ke CMS">
    <form method="POST" action="{{ route('cms.login.store') }}" class="space-y-4">
        @csrf
        <label class="block">
            <span class="text-sm font-medium">Email</span>
            <input name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
        </label>
        <label class="block">
            <span class="text-sm font-medium">Password</span>
            <input name="password" type="password" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
        </label>
        <label class="flex items-center gap-2 text-sm text-zinc-600">
            <input name="remember" type="checkbox" value="1" class="rounded border-zinc-300">
            Remember me
        </label>
        <button class="w-full rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Login</button>
    </form>
    <p class="mt-5 text-center text-sm text-zinc-600">
        Belum punya akun?
        <a href="{{ route('cms.register') }}" class="font-medium text-zinc-950 underline">Register</a>
    </p>
</x-cms.layouts.guest>
