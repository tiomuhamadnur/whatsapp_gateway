<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'direction',
        'target_type',
        'to_number',
        'from_number',
        'broadcast_targets',
        'type',
        'content',
        'media_url',
        'payload',
        'status',
        'wa_message_id',
        'error_message',
        'retry_count',
        'scheduled_at',
        'recurrence',
        'recurrence_interval',
        'recurrence_until',
        'parent_message_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'broadcast_targets' => 'array',
            'payload' => 'array',
            'recurrence_until' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function whatsappSession(): BelongsTo
    {
        return $this->belongsTo(WhatsappSession::class, 'session_id', 'session_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_message_id');
    }
}
