<?php

namespace App\Services;

use App\Jobs\SendOrderNotification;
use App\Models\NotificationSetting;
use App\Models\Order;
use App\Models\Outlet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public const EVENT_NEW_ORDER = 'new_order';
    public const EVENT_ORDER_READY = 'order_ready';

    public function settings(Outlet $outlet): NotificationSetting
    {
        return NotificationSetting::firstOrCreate(
            ['outlet_id' => $outlet->id],
            NotificationSetting::defaultsFor($outlet->id)
        );
    }

    public function queueOrderEvent(Order $order, string $event): void
    {
        if (! config('notifications.enabled', true)) {
            return;
        }

        SendOrderNotification::dispatch($order->id, $event);
    }

    public function sendOrderEvent(Order $order, string $event): void
    {
        $settings = $this->settings($order->outlet);

        if (! $settings->is_enabled) {
            return;
        }

        if ($event === self::EVENT_NEW_ORDER && ! $settings->notify_new_order) {
            return;
        }

        if ($event === self::EVENT_ORDER_READY && ! $settings->notify_order_ready) {
            return;
        }

        $message = $this->formatOrderMessage($order, $event);

        $this->sendStaffTelegram($settings, $message);
        $this->sendStaffWhatsApp($settings, $message, $event);

        if ($event === self::EVENT_ORDER_READY && $settings->notify_customer_ready) {
            $this->sendCustomerReadyWhatsApp($settings, $order);
        }
    }

    public function sendTest(NotificationSetting $settings): void
    {
        if (! $settings->is_enabled) {
            throw new \RuntimeException('Aktifkan notifikasi terlebih dahulu.');
        }

        $message = '🔔 Test notifikasi ' . config('app.name') . ' — ' . ($settings->outlet->name ?? 'Outlet') . ' · ' . now()->format('d/m/Y H:i');

        $sent = false;

        if ($settings->hasTelegram()) {
            $this->sendTelegram($settings, $message);
            $sent = true;
        }

        if ($settings->hasWhatsApp()) {
            $this->sendWhatsApp($settings, $message, $settings->whatsapp_target);
            $sent = true;
        }

        if (! $sent) {
            throw new \RuntimeException('Isi minimal Telegram atau WhatsApp.');
        }
    }

    public function formatOrderMessage(Order $order, string $event): string
    {
        $order->loadMissing(['items', 'outlet', 'diningTable']);

        $lines = match ($event) {
            self::EVENT_ORDER_READY => [
                '✅ *Pesanan siap disajikan*',
                $order->outlet->name,
                '',
                'No: ' . $order->order_number,
                'Pelanggan: ' . $order->displayCustomer(),
            ],
            default => [
                '🆕 *Pesanan baru*',
                $order->outlet->name,
                '',
                'No: ' . $order->order_number,
                'Pelanggan: ' . $order->displayCustomer(),
                'Sumber: ' . $order->sourceEnum()->label(),
            ],
        };

        if ($order->customer_phone && $order->customer_phone !== '-') {
            $lines[] = 'Tel: ' . $order->customer_phone;
        }

        if ($order->notes) {
            $lines[] = 'Catatan: ' . $order->notes;
        }

        $lines[] = '';
        $lines[] = 'Item:';

        foreach ($order->items as $item) {
            $line = '• ' . $item->qty . '× ' . $item->item_name;
            if ($item->modifierSummary()) {
                $line .= ' (' . $item->modifierSummary() . ')';
            }
            if ($item->note) {
                $line .= ' — ' . $item->note;
            }
            $lines[] = $line;
        }

        $lines[] = '';
        $lines[] = 'Total: Rp ' . number_format($order->total, 0, ',', '.');

        return implode("\n", $lines);
    }

    private function sendStaffTelegram(NotificationSetting $settings, string $message): void
    {
        if (! $settings->hasTelegram()) {
            return;
        }

        $this->sendTelegram($settings, $message);
    }

    private function sendStaffWhatsApp(NotificationSetting $settings, string $message, string $event): void
    {
        if (! $settings->hasWhatsApp() || ! filled($settings->whatsapp_target)) {
            return;
        }

        $this->sendWhatsApp($settings, $message, $settings->whatsapp_target);
    }

    private function sendCustomerReadyWhatsApp(NotificationSetting $settings, Order $order): void
    {
        if ($settings->whatsapp_provider !== NotificationSetting::WHATSAPP_FONNTE) {
            return;
        }

        if (! filled($settings->whatsapp_fonnte_token) || ! $this->isEligibleCustomerPhone($order->customer_phone)) {
            return;
        }

        $phone = $this->normalizeWhatsAppPhone($order->customer_phone);
        $message = 'Halo ' . $order->customer_name . ", pesanan {$order->order_number} sudah siap disajikan. Terima kasih! — {$order->outlet->name}";

        $this->sendWhatsApp($settings, $message, $phone);
    }

    private function sendTelegram(NotificationSetting $settings, string $message): void
    {
        try {
            $response = Http::timeout(10)->post(
                'https://api.telegram.org/bot' . $settings->telegram_bot_token . '/sendMessage',
                [
                    'chat_id' => $settings->telegram_chat_id,
                    'text' => $message,
                ]
            );

            if (! $response->successful()) {
                Log::warning('Telegram notification failed', [
                    'outlet_id' => $settings->outlet_id,
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram notification error', [
                'outlet_id' => $settings->outlet_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendWhatsApp(NotificationSetting $settings, string $message, ?string $target): void
    {
        if (! filled($target)) {
            return;
        }

        try {
            if ($settings->whatsapp_provider === NotificationSetting::WHATSAPP_FONNTE) {
                $this->sendFonnte($settings, $message, $target);

                return;
            }

            $request = Http::timeout(10)->acceptJson();

            if (filled($settings->whatsapp_webhook_secret)) {
                $request = $request->withToken($settings->whatsapp_webhook_secret);
            }

            $response = $request->post($settings->whatsapp_webhook_url, [
                'message' => $message,
                'text' => $message,
                'target' => $target,
                'event' => 'qr_pos_notification',
            ]);

            if (! $response->successful()) {
                Log::warning('WhatsApp webhook notification failed', [
                    'outlet_id' => $settings->outlet_id,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp notification error', [
                'outlet_id' => $settings->outlet_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendFonnte(NotificationSetting $settings, string $message, string $target): void
    {
        $response = Http::timeout(10)
            ->withHeaders(['Authorization' => $settings->whatsapp_fonnte_token])
            ->asForm()
            ->post('https://api.fonnte.com/send', [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62',
            ]);

        if (! $response->successful()) {
            Log::warning('Fonnte notification failed', [
                'outlet_id' => $settings->outlet_id,
                'body' => $response->body(),
            ]);
        }
    }

    private function isEligibleCustomerPhone(?string $phone): bool
    {
        if ($phone === null || $phone === '' || $phone === '-') {
            return false;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return strlen($digits) >= 10;
    }

    private function normalizeWhatsAppPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        if (! str_starts_with($digits, '62')) {
            $digits = '62' . ltrim($digits, '0');
        }

        return $digits;
    }
}
