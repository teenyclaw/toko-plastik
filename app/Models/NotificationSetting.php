<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    public const WHATSAPP_WEBHOOK = 'webhook';
    public const WHATSAPP_FONNTE = 'fonnte';

    protected $fillable = [
        'outlet_id',
        'is_enabled',
        'notify_new_order',
        'notify_order_ready',
        'notify_customer_ready',
        'telegram_bot_token',
        'telegram_chat_id',
        'whatsapp_provider',
        'whatsapp_webhook_url',
        'whatsapp_webhook_secret',
        'whatsapp_fonnte_token',
        'whatsapp_target',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'notify_new_order' => 'boolean',
        'notify_order_ready' => 'boolean',
        'notify_customer_ready' => 'boolean',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function hasTelegram(): bool
    {
        return filled($this->telegram_bot_token) && filled($this->telegram_chat_id);
    }

    public function hasWhatsApp(): bool
    {
        if ($this->whatsapp_provider === self::WHATSAPP_FONNTE) {
            return filled($this->whatsapp_fonnte_token) && filled($this->whatsapp_target);
        }

        return filled($this->whatsapp_webhook_url);
    }

    public static function defaultsFor(int $outletId): array
    {
        return [
            'outlet_id' => $outletId,
            'is_enabled' => false,
            'notify_new_order' => true,
            'notify_order_ready' => false,
            'notify_customer_ready' => false,
            'whatsapp_provider' => self::WHATSAPP_WEBHOOK,
        ];
    }
}
