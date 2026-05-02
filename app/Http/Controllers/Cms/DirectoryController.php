<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\WhatsappContact;
use App\Models\WhatsappGroup;
use App\Models\WhatsappSession;
use App\Services\WhatsAppNodeService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class DirectoryController extends Controller
{
    public function groups(Request $request, string $sessionId, WhatsAppNodeService $node): View
    {
        $session = $this->ownedSession($request, $sessionId);
        $groups = WhatsappGroup::query()
            ->where('user_id', $request->user()->id)
            ->where('session_id', $session->session_id)
            ->latest('synced_at')
            ->get()
            ->toArray();
        $error = null;

        try {
            $freshGroups = $node->getGroups($session->session_id);

            foreach ($freshGroups as $group) {
                WhatsappGroup::query()->updateOrCreate(
                    ['group_id' => $group['id']],
                    [
                        'user_id' => $request->user()->id,
                        'session_id' => $session->session_id,
                        'name' => $group['name'] ?? null,
                        'participants_count' => $group['participants_count'] ?? 0,
                        'owner' => $group['owner'] ?? null,
                        'synced_at' => now(),
                    ]
                );
            }

            $groups = WhatsappGroup::query()
                ->where('user_id', $request->user()->id)
                ->where('session_id', $session->session_id)
                ->latest('synced_at')
                ->get()
                ->toArray();
        } catch (Throwable $throwable) {
            report($throwable);
            $error = $throwable->getMessage();
        }

        return view('cms.directories.groups', compact('session', 'groups', 'error'));
    }

    public function contacts(Request $request, string $sessionId, WhatsAppNodeService $node): View
    {
        $session = $this->ownedSession($request, $sessionId);
        $contacts = WhatsappContact::query()
            ->where('user_id', $request->user()->id)
            ->where('session_id', $session->session_id)
            ->latest('synced_at')
            ->get()
            ->toArray();
        $error = null;

        try {
            $freshContacts = $node->getContacts($session->session_id);

            foreach ($freshContacts as $contact) {
                WhatsappContact::query()->updateOrCreate(
                    ['contact_id' => $contact['id']],
                    [
                        'user_id' => $request->user()->id,
                        'session_id' => $session->session_id,
                        'number' => $contact['number'] ?? null,
                        'name' => $contact['name'] ?? null,
                        'source' => $contact['source'] ?? null,
                        'synced_at' => now(),
                    ]
                );
            }

            $contacts = WhatsappContact::query()
                ->where('user_id', $request->user()->id)
                ->where('session_id', $session->session_id)
                ->latest('synced_at')
                ->get()
                ->toArray();
        } catch (Throwable $throwable) {
            report($throwable);
            $error = $throwable->getMessage();
        }

        return view('cms.directories.contacts', compact('session', 'contacts', 'error'));
    }

    private function ownedSession(Request $request, string $sessionId): WhatsappSession
    {
        return WhatsappSession::query()
            ->where('user_id', $request->user()->id)
            ->where('session_id', $sessionId)
            ->firstOrFail();
    }
}
