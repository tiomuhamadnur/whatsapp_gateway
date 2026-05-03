<x-cms.layouts.app title="Profile" heading="Profile" eyebrow="Account">
    <section class="grid gap-6 xl:grid-cols-[280px_1fr]">
        <div class="rounded-lg border border-zinc-200 bg-white p-6">
            <div class="flex flex-col items-center gap-4 text-center">
                @if ($user->profilePhotoUrl())
                    <img src="{{ $user->profilePhotoUrl() }}" alt="Profile photo" class="h-24 w-24 rounded-full object-cover">
                @else
                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-zinc-100 text-3xl font-semibold text-zinc-700">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <div class="text-lg font-semibold">{{ $user->name }}</div>
                    <div class="text-sm text-zinc-500">{{ $user->email }}</div>
                </div>
            </div>

            <div class="mt-6 space-y-4 text-sm text-zinc-500">
                <p>Update your username, email, password, and profile photo from this page.</p>
                <p>Leave password blank if you don't want to change it.</p>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6">
            <form method="POST" action="{{ route('cms.profile.update') }}" enctype="multipart/form-data" class="grid gap-4">
                @csrf
                @method('PATCH')

                <label>
                    <span class="text-sm font-medium">Username <span class="required-mark">*</span></span>
                    <input name="name" value="{{ old('name', $user->name) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                </label>

                <label>
                    <span class="text-sm font-medium">Email address <span class="required-mark">*</span></span>
                    <input name="email" type="email" value="{{ old('email', $user->email) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                </label>

                <label>
                    <span class="text-sm font-medium">Profile photo</span>
                    <input name="photo" type="file" accept="image/*" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none file:mr-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-700 focus:border-zinc-950">
                </label>

                <label>
                    <span class="text-sm font-medium">Current password</span>
                    <input name="current_password" type="password" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                </label>

                <label>
                    <span class="text-sm font-medium">New password</span>
                    <input name="password" type="password" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                </label>

                <label>
                    <span class="text-sm font-medium">Confirm new password</span>
                    <input name="password_confirmation" type="password" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                </label>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Save profile</button>
                </div>
            </form>
        </div>
    </section>
</x-cms.layouts.app>
