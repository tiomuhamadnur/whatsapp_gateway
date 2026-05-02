<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class WhatsAppNodeService
{
    public function connectSession(string $sessionId): array
    {
        return $this->client()
            ->post('/sessions/connect', ['session_id' => $sessionId])
            ->throw()
            ->json();
    }

    public function getQr(string $sessionId): ?string
    {
        return $this->client()
            ->get('/sessions/qr', ['session_id' => $sessionId])
            ->throw()
            ->json('data.qr');
    }

    public function getStatus(string $sessionId): array
    {
        return $this->client()
            ->get('/sessions/status', ['session_id' => $sessionId])
            ->throw()
            ->json('data', []);
    }

    public function disconnectSession(string $sessionId): array
    {
        return $this->client()
            ->post('/sessions/disconnect', ['session_id' => $sessionId])
            ->throw()
            ->json();
    }

    public function getGroups(string $sessionId): array
    {
        return $this->client()
            ->get('/sessions/groups', ['session_id' => $sessionId])
            ->throw()
            ->json('data', []);
    }

    public function getContacts(string $sessionId): array
    {
        return $this->client()
            ->get('/sessions/contacts', ['session_id' => $sessionId])
            ->throw()
            ->json('data', []);
    }

    public function sendMessage(array $payload): array
    {
        return $this->client()
            ->post('/messages/send', $payload)
            ->throw()
            ->json();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim(config('services.node_wa.url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->withToken(config('services.node_wa.secret'));
    }
}
