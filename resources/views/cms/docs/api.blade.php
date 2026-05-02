<x-cms.layouts.app title="API Docs" heading="API Documentation" eyebrow="Developer">
    <section class="mb-6 rounded-lg border border-zinc-200 bg-white p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold">Postman Collection</h2>
                <p class="mt-1 text-sm text-zinc-500">Import collection ini, lalu isi variable `base_url`, `token`, dan `session_id` di Postman.</p>
            </div>
            <a href="{{ $postmanUrl }}" download class="inline-flex rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Download Collection</a>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[280px_1fr]">
        <aside class="rounded-lg border border-zinc-200 bg-white p-4 text-sm">
            <h2 class="font-semibold">Variables</h2>
            <dl class="mt-3 space-y-3">
                <div>
                    <dt class="font-medium">base_url</dt>
                    <dd class="text-zinc-500">http://127.0.0.1:8000</dd>
                </div>
                <div>
                    <dt class="font-medium">token</dt>
                    <dd class="text-zinc-500">Buat dari menu API Tokens atau endpoint login.</dd>
                </div>
                <div>
                    <dt class="font-medium">session_id</dt>
                    <dd class="text-zinc-500">UUID dari sesi WhatsApp.</dd>
                </div>
            </dl>
            <div class="mt-5 border-t border-zinc-200 pt-4">
                <h2 class="font-semibold">Plan Rules</h2>
                <p class="mt-2 text-zinc-500">Free menambahkan footer otomatis. Starter hanya text. Media mendukung image/video. Complete dan Custom bisa webhook sesuai konfigurasi owner.</p>
            </div>
        </aside>

        <div class="space-y-4">
            @php
                $endpoints = [
                    ['POST', '/api/auth/register', 'Register user baru dan otomatis membuat subscription free.', '{"name":"Demo","email":"demo@example.com","password":"password123","password_confirmation":"password123"}'],
                    ['POST', '/api/auth/login', 'Login dan ambil bearer token.', '{"email":"demo@example.com","password":"password123"}'],
                    ['POST', '/api/wa/sessions', 'Buat sesi WhatsApp baru dan minta Node WA membuat QR.', '{"name":"Nomor CS"}'],
                    ['GET', '/api/wa/sessions', 'List sesi milik user.', null],
                    ['GET', '/api/wa/sessions/{{session_id}}/qr', 'Ambil QR base64 untuk discan.', null],
                    ['GET', '/api/wa/sessions/{{session_id}}/status', 'Cek status connected/disconnected.', null],
                    ['GET', '/api/wa/sessions/{{session_id}}/groups', 'Ambil list group ID dari device yang connected.', null],
                    ['GET', '/api/wa/sessions/{{session_id}}/contacts', 'Ambil list kontak yang diketahui session.', null],
                    ['DELETE', '/api/wa/sessions/{{session_id}}', 'Disconnect sesi.', null],
                    ['POST', '/api/messages/send', 'Kirim pesan text ke kontak via queue.', '{"session_id":"{{session_id}}","target_type":"contact","to":"6281234567890","type":"text","message":"Halo dari WA Gateway"}'],
                    ['POST', '/api/messages/send', 'Kirim pesan ke group ID.', '{"session_id":"{{session_id}}","target_type":"group","to":"120363xxxxx@g.us","type":"text","message":"Halo group"}'],
                    ['POST', '/api/messages/send', 'Broadcast ke banyak nomor/group.', '{"session_id":"{{session_id}}","target_type":"broadcast","targets":["6281234567890","6289876543210","120363xxxxx@g.us"],"type":"text","message":"Broadcast promo"}'],
                    ['POST', '/api/messages/send', 'Jadwalkan pesan dan repeat daily/weekly/monthly/custom.', '{"session_id":"{{session_id}}","target_type":"contact","to":"6281234567890","type":"text","message":"Reminder","scheduled_at":"2026-05-03 09:00:00","recurrence":"daily","recurrence_interval":1,"recurrence_until":"2026-06-03 09:00:00"}'],
                    ['GET', '/api/messages', 'History pesan. Bisa tambah query `session_id`, `page`, dan `per_page`.', null],
                ];
            @endphp

            @foreach ($endpoints as [$method, $path, $description, $body])
                <article class="rounded-lg border border-zinc-200 bg-white p-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-16 rounded-md bg-zinc-950 px-2 py-1 text-center text-xs font-semibold text-white">{{ $method }}</span>
                            <code class="text-sm font-semibold">{{ $path }}</code>
                        </div>
                    </div>
                    <p class="mt-3 text-sm text-zinc-600">{{ $description }}</p>
                    @if ($body)
                        <pre class="mt-3 overflow-x-auto rounded-md bg-zinc-950 p-3 text-sm text-white">{{ $body }}</pre>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
</x-cms.layouts.app>
