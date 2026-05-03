<?php

namespace App\Livewire\Sessions;

use App\Models\WhatsappSession;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SessionCards extends Component
{
    use WithPagination;

    public function render(): View
    {
        return view('livewire.sessions.session-cards', [
            'sessions' => WhatsappSession::query()
                ->where('user_id', auth()->id())
                ->withCount(['groups', 'contacts'])
                ->latest()
                ->paginate(12),
        ]);
    }
}
