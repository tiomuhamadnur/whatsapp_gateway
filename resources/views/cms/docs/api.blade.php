<x-cms.layouts.app title="API Docs" heading="API Documentation" eyebrow="Developer">
    <section class="mb-6 rounded-lg border border-zinc-200 bg-white p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold">Postman Collection</h2>
                <p class="mt-1 text-sm text-zinc-500">Import this collection, then set the `base_url`, `token`, and `session_id` variables in Postman.</p>
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
                    <dd class="text-zinc-500">Create it from API Tokens or the login endpoint.</dd>
                </div>
                <div>
                    <dt class="font-medium">session_id</dt>
                    <dd class="text-zinc-500">The UUID of a WhatsApp session.</dd>
                </div>
            </dl>
            <div class="mt-5 border-t border-zinc-200 pt-4">
                <h2 class="font-semibold">Plan Rules</h2>
                <p class="mt-2 text-zinc-500">Free adds an automatic footer. Starter supports text only. Media supports image and video. Complete and Custom can use webhooks based on owner configuration.</p>
            </div>
        </aside>

        <div class="space-y-4">
            @php
                $endpoints = [
                    ['POST', '/api/auth/register', 'Register a new user and create a free subscription automatically.', json_encode([
                        'name' => 'Demo',
                        'email' => 'demo@example.com',
                        'password' => 'password123',
                        'password_confirmation' => 'password123'
                    ], JSON_PRETTY_PRINT)],
                    ['POST', '/api/auth/login', 'Log in and receive a bearer token.', json_encode([
                        'email' => 'demo@example.com',
                        'password' => 'password123'
                    ], JSON_PRETTY_PRINT)],
                    ['POST', '/api/wa/sessions', 'Create a WhatsApp session and ask Node WA to generate a QR code.', json_encode([
                        'name' => 'Customer Service Number'
                    ], JSON_PRETTY_PRINT)],
                    ['GET', '/api/wa/sessions', 'List sessions owned by the authenticated user.', null],
                    ['GET', '/api/wa/sessions/{{session_id}}/qr', 'Get the base64 QR code for scanning.', null],
                    ['GET', '/api/wa/sessions/{{session_id}}/status', 'Check whether the session is connected or disconnected.', null],
                    ['GET', '/api/wa/sessions/{{session_id}}/groups', 'Get group IDs from the connected device.', null],
                    ['GET', '/api/wa/sessions/{{session_id}}/contacts', 'Get contacts known by this session.', null],
                    ['DELETE', '/api/wa/sessions/{{session_id}}', 'Disconnect a session.', null],
                    ['POST', '/api/messages/send', 'Send a text message to a contact through the queue.', json_encode([
                        'session_id' => '{{session_id}}',
                        'target_type' => 'contact',
                        'to' => '6281234567890',
                        'type' => 'text',
                        'message' => 'Hello from WA Gateway'
                    ], JSON_PRETTY_PRINT)],
                    ['POST', '/api/messages/send', 'Send a message to a group ID.', json_encode([
                        'session_id' => '{{session_id}}',
                        'target_type' => 'group',
                        'to' => '120363xxxxx@g.us',
                        'type' => 'text',
                        'message' => 'Hello group'
                    ], JSON_PRETTY_PRINT)],
                    ['POST', '/api/messages/send', 'Broadcast to multiple numbers or groups.', json_encode([
                        'session_id' => '{{session_id}}',
                        'target_type' => 'broadcast',
                        'targets' => ['6281234567890', '6289876543210', '120363xxxxx@g.us'],
                        'type' => 'text',
                        'message' => 'Promo broadcast'
                    ], JSON_PRETTY_PRINT)],
                    ['POST', '/api/messages/send', 'Schedule a message and repeat it daily, weekly, monthly, or with a custom interval.', json_encode([
                        'session_id' => '{{session_id}}',
                        'target_type' => 'contact',
                        'to' => '6281234567890',
                        'type' => 'text',
                        'message' => 'Reminder',
                        'scheduled_at' => '2026-05-03 09:00:00',
                        'recurrence' => 'daily',
                        'recurrence_interval' => 1,
                        'recurrence_until' => '2026-06-03 09:00:00'
                    ], JSON_PRETTY_PRINT)],
                    ['GET', '/api/messages', 'Message history. Optional query parameters: `session_id`, `page`, and `per_page`.', null],
                ];
            @endphp

            @foreach ($endpoints as [$method, $path, $description, $body])
                <article class="rounded-lg border border-zinc-200 bg-white p-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-16 rounded-md bg-zinc-950 px-2 py-1 text-center text-xs font-semibold text-white">{{ $method }}</span>
                            <code class="wrap-anywhere text-sm font-semibold">{{ $path }}</code>
                        </div>
                    </div>
                    <p class="mt-3 text-sm text-zinc-600">{{ $description }}</p>
                    @if ($body)
                        <pre class="wrap-anywhere mt-3 whitespace-pre-wrap rounded-md bg-zinc-950 p-3 text-sm text-white">{{ $body }}</pre>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
</x-cms.layouts.app>
