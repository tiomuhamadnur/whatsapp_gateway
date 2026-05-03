<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Message;
use App\Models\MessageLog;
use App\Models\WhatsappSession;
use App\Services\QuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Yajra\DataTables\Facades\DataTables;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $query = Message::query()
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->query('session_id'));
        }

        return view('cms.messages.index', [
            'messages' => $query->paginate(15)->withQueryString(),
            'sessions' => WhatsappSession::query()
                ->where('user_id', $request->user()->id)
                ->where('status', 'connected')
                ->latest()
                ->get(),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $query = Message::query()
            ->where('user_id', $request->user()->id)
            ->latest();

        return DataTables::eloquent($query)
            ->editColumn('id', fn (Message $message): string => number_format($message->id))
            ->addColumn('target', fn (Message $message): string => $message->to_number ?: $message->from_number ?: '-')
            ->editColumn('created_at', fn (Message $message): string => $message->created_at->format('Y-m-d H:i'))
            ->toJson();
    }

    public function store(Request $request, QuotaService $quota): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'uuid'],
            'to' => ['required', 'string', 'max:100'],
            'target_type' => ['nullable', 'in:contact,group'],
            'type' => ['required', 'in:text,image,document,audio,video,location,buttons'],
            'message' => ['required', 'string'],
            'media_url' => ['nullable', 'url', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'buttons' => ['nullable', 'json'],
            'scheduled_at' => ['nullable', 'date'],
            'recurrence' => ['nullable', 'in:none,daily,weekly,monthly,custom'],
            'recurrence_interval' => ['nullable', 'integer', 'min:1', 'max:365'],
            'recurrence_until' => ['nullable', 'date', 'after:scheduled_at'],
        ], [], [
            'session_id' => 'connected WhatsApp session',
            'to' => 'recipient phone number or group ID',
            'target_type' => 'target type',
            'media_url' => 'media URL',
            'scheduled_at' => 'scheduled send time',
            'recurrence_interval' => 'repeat interval',
            'recurrence_until' => 'repeat end time',
        ]);

        $user = $request->user();
        $sessionExists = WhatsappSession::query()
            ->where('user_id', $user->id)
            ->where('session_id', $validated['session_id'])
            ->where('status', 'connected')
            ->exists();

        if (! $sessionExists) {
            return back()->withErrors(['session_id' => 'Choose a WhatsApp session that is currently connected.'])->withInput();
        }

        if (! $quota->canSendType($user, $validated['type'])) {
            return back()->withErrors(['type' => 'Your current plan does not support this message type.'])->withInput();
        }

        // Validate location fields
        if ($validated['type'] === 'location') {
            if (empty($validated['latitude']) || empty($validated['longitude'])) {
                return back()->withErrors(['latitude' => 'Latitude and longitude are required for location messages.'])->withInput();
            }
        }

        // Validate buttons
        if ($validated['type'] === 'buttons') {
            $buttons = json_decode($validated['buttons'] ?? '[]', true);
            if (!is_array($buttons) || empty($buttons)) {
                return back()->withErrors(['buttons' => 'At least one button is required for button messages.'])->withInput();
            }
            foreach ($buttons as $button) {
                if (!isset($button['text']) || !isset($button['id'])) {
                    return back()->withErrors(['buttons' => 'Each button must have text and id.'])->withInput();
                }
            }
        }

        if (! $quota->hasQuota($user)) {
            return back()->withErrors(['message' => 'Your message quota has been used up. Upgrade your plan to send more messages.'])->withInput();
        }

        try {
            $quota->decrementQuota($user);
        } catch (RuntimeException) {
            return back()->withErrors(['message' => 'Your message quota has been used up. Upgrade your plan to send more messages.'])->withInput();
        }

        $status = isset($validated['scheduled_at']) && now()->lt($validated['scheduled_at']) ? 'scheduled' : 'queued';

        $payload = [];
        if ($validated['type'] === 'location') {
            $payload = [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'address' => $validated['address'] ?? null,
            ];
        } elseif ($validated['type'] === 'buttons') {
            $payload = [
                'buttons' => json_decode($validated['buttons'], true),
            ];
        }

        $message = Message::query()->create([
            'user_id' => $user->id,
            'session_id' => $validated['session_id'],
            'direction' => 'outbound',
            'target_type' => $validated['target_type'] ?? 'contact',
            'to_number' => $validated['to'],
            'type' => $validated['type'],
            'content' => $quota->applyMessagePolicy($user, $validated['type'], $validated['message']),
            'media_url' => $validated['media_url'] ?? null,
            'payload' => $payload,
            'status' => $status,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'recurrence' => ($validated['recurrence'] ?? 'none') === 'none' ? null : $validated['recurrence'],
            'recurrence_interval' => $validated['recurrence_interval'] ?? 1,
            'recurrence_until' => $validated['recurrence_until'] ?? null,
        ]);

        MessageLog::query()->create([
            'message_id' => $message->id,
            'event' => $status,
            'description' => $status === 'scheduled' ? 'Message scheduled from CMS.' : 'Message queued from CMS.',
            'created_at' => now(),
        ]);

        if ($status === 'queued') {
            SendWhatsAppMessage::dispatch($message->id);
        }

        return redirect()->route('cms.messages.index')->with('status', 'Message queued successfully.');
    }
}
