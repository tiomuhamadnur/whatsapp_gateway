<x-cms.layouts.guest title="Register" heading="Buat akun CMS">
    <form method="POST" action="{{ route('cms.register.store') }}" class="space-y-4">
        @csrf
        <label class="block">
            <span class="text-sm font-medium">Nama</span>
            <input name="name" type="text" value="{{ old('name') }}" required autofocus class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
        </label>
        <label class="block">
            <span class="text-sm font-medium">Email</span>
            <input name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
        </label>
        <label class="block">
            <span class="text-sm font-medium">Password</span>
            <input name="password" type="password" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
        </label>
        <label class="block">
            <span class="text-sm font-medium">Konfirmasi Password</span>
            <input name="password_confirmation" type="password" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
        </label>
        <button class="w-full rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Register</button>
    </form>
    <p class="mt-5 text-center text-sm text-zinc-600">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-medium text-zinc-950 underline">Login</a>
    </p>
</x-cms.layouts.guest>
