<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'contact_id',
        'number',
        'name',
        'source',
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
