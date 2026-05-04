<x-cms.layouts.app title="Profile" heading="Profile" eyebrow="Account">
    <section class="grid gap-6 xl:grid-cols-[280px_1fr]">
        <div class="rounded-lg border border-zinc-200 bg-white p-6">
            <div class="flex flex-col items-center gap-4 text-center">
                <div id="photo-preview-container" class="relative group cursor-pointer">
                    @if ($user->profilePhotoUrl())
                        <img id="photo-preview" src="{{ $user->profilePhotoUrl() }}" alt="Profile photo" class="h-24 w-24 rounded-full object-cover">
                    @else
                        <div id="photo-preview" class="flex h-24 w-24 items-center justify-center rounded-full bg-zinc-100 text-3xl font-semibold text-zinc-700">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="absolute inset-0 rounded-full bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <i class="fa-solid fa-camera text-white text-lg"></i>
                    </div>
                </div>
                <div>
                    <div class="text-lg font-semibold">{{ $user->name }}</div>
                    <div class="text-sm text-zinc-500">{{ $user->email }}</div>
                </div>
            </div>

            <div class="mt-6 space-y-2 text-sm text-zinc-500">
                <p>Click on your photo to upload and crop a new one. Max 1MB.</p>
                <p>Update your profile info below.</p>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Profile Update Form -->
            <div class="rounded-lg border border-zinc-200 bg-white p-6">
                <h3 class="text-base font-semibold mb-4">Profile Information</h3>
                <form method="POST" action="{{ route('cms.profile.update') }}" class="grid gap-4">
                    @csrf
                    @method('PATCH')

                    <label>
                        <span class="text-sm font-medium">Full Name <span class="required-mark">*</span></span>
                        <input name="name" value="{{ old('name', $user->name) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                        @error('name')
                            <span class="text-xs text-red-600 mt-1">{{ $message }}</span>
                        @enderror
                    </label>

                    <label>
                        <span class="text-sm font-medium">Username <span class="required-mark">*</span></span>
                        <input name="username" value="{{ old('username', $user->username) }}" required placeholder="@username" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                        @error('username')
                            <span class="text-xs text-red-600 mt-1">{{ $message }}</span>
                        @enderror
                    </label>

                    <label>
                        <span class="text-sm font-medium">Email address <span class="required-mark">*</span></span>
                        <input name="email" type="email" value="{{ old('email', $user->email) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                        @error('email')
                            <span class="text-xs text-red-600 mt-1">{{ $message }}</span>
                        @enderror
                    </label>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Save changes</button>
                    </div>
                </form>
            </div>

            <!-- Photo Upload Form (Hidden) -->
            <input type="file" id="photo-input" name="photo" accept="image/*" class="hidden" data-photo-input>

            <!-- Password Change Form -->
            <div class="rounded-lg border border-zinc-200 bg-white p-6">
                <h3 class="text-base font-semibold mb-4">Change Password</h3>
                <form method="POST" action="{{ route('cms.profile.update-password') }}" class="grid gap-4">
                    @csrf
                    @method('PATCH')

                    <label>
                        <span class="text-sm font-medium">Current password <span class="required-mark">*</span></span>
                        <input name="current_password" type="password" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                        @error('current_password')
                            <span class="text-xs text-red-600 mt-1">{{ $message }}</span>
                        @enderror
                    </label>

                    <label>
                        <span class="text-sm font-medium">New password <span class="required-mark">*</span></span>
                        <input name="password" type="password" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                        @error('password')
                            <span class="text-xs text-red-600 mt-1">{{ $message }}</span>
                        @enderror
                    </label>

                    <label>
                        <span class="text-sm font-medium">Confirm new password <span class="required-mark">*</span></span>
                        <input name="password_confirmation" type="password" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-zinc-950">
                    </label>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Update password</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Photo Crop Modal -->
    <dialog id="crop-modal" class="w-[min(500px,calc(100vw-2rem))] rounded-lg border p-0 shadow-2xl backdrop:bg-black/40">
        <div class="p-5">
            <h2 class="text-base font-semibold">Crop Photo</h2>
            <div class="mt-4">
                <img id="crop-image" src="" class="w-full rounded-md" style="max-height: 400px; object-fit: contain;">
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" id="crop-cancel" class="rounded-md border px-3 py-2 text-sm font-medium hover:bg-zinc-100">Cancel</button>
                <button type="button" id="crop-confirm" class="rounded-md bg-zinc-950 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Apply Crop</button>
            </div>
        </div>
    </dialog>

    <script>
        const photoPreviewContainer = document.getElementById('photo-preview-container');
        const photoInput = document.getElementById('photo-input');
        const cropModal = document.getElementById('crop-modal');
        const cropImage = document.getElementById('crop-image');
        const cropCancel = document.getElementById('crop-cancel');
        const cropConfirm = document.getElementById('crop-confirm');
        const photoPreview = document.getElementById('photo-preview');

        let selectedFile = null;
        const MAX_FILE_SIZE = 1024 * 1024; // 1MB
        const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

        // Trigger file input when clicking on photo
        photoPreviewContainer.addEventListener('click', () => {
            photoInput.click();
        });

        // Handle file selection with validation
        photoInput.addEventListener('change', async (e) => {
            const file = e.target.files?.[0];
            if (!file) return;

            // Validation
            const errors = [];

            if (!ALLOWED_TYPES.includes(file.type)) {
                errors.push('Only JPEG, PNG, and WebP images are allowed');
            }

            if (file.size > MAX_FILE_SIZE) {
                errors.push(`File size must be less than 1MB (current: ${(file.size / 1024 / 1024).toFixed(2)}MB)`);
            }

            if (errors.length > 0) {
                alert('Invalid file:\n\n' + errors.join('\n'));
                photoInput.value = '';
                return;
            }

            selectedFile = file;
            const reader = new FileReader();

            reader.onerror = () => {
                alert('Error reading file. Please try again.');
                photoInput.value = '';
                selectedFile = null;
            };

            reader.onload = (event) => {
                cropImage.src = event.target.result;
                cropModal.showModal();
            };

            reader.readAsDataURL(file);
        });

        cropCancel.addEventListener('click', () => {
            cropModal.close();
            photoInput.value = '';
            selectedFile = null;
        });

        cropConfirm.addEventListener('click', async () => {
            if (!selectedFile) return;

            try {
                cropConfirm.disabled = true;
                cropConfirm.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';

                const formData = new FormData();
                formData.append('photo', selectedFile);
                formData.append('_method', 'PATCH');
                formData.append('_token', document.querySelector('input[name="_token"]').value);

                const response = await fetch('{{ route("cms.profile.update-photo") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                if (!response.ok) {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        const data = await response.json();
                        throw new Error(data.message || 'Upload failed');
                    } else {
                        throw new Error(`Upload failed with status ${response.status}`);
                    }
                }

                // Success - reload page after delay
                await new Promise(resolve => setTimeout(resolve, 500));
                location.reload();
            } catch (error) {
                console.error('Upload error:', error);
                alert('Failed to upload photo:\n\n' + (error.message || 'Unknown error'));
                cropConfirm.disabled = false;
                cropConfirm.innerHTML = 'Apply Crop';
            }
        });
    </script>
</x-cms.layouts.app>
