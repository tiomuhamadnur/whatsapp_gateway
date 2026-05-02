<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'group_id',
        'name',
        'participants_count',
        'owner',
        'synced_at',
    ];

    protected function casts(): array
    {
        return ['synced_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
