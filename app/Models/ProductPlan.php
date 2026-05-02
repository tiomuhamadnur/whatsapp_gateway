<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_period',
        'daily_message_quota',
        'max_sessions',
        'allowed_message_types',
        'can_send_media',
        'can_use_webhook',
        'enforce_footer',
        'footer_text',
        'is_custom',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'allowed_message_types' => 'array',
            'price' => 'integer',
            'can_send_media' => 'boolean',
            'can_use_webhook' => 'boolean',
            'enforce_footer' => 'boolean',
            'is_custom' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
