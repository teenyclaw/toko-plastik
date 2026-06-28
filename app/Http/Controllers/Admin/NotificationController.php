<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index()
    {
        $outlet = current_outlet();
        $settings = $this->notifications->settings($outlet);

        return view('admin.notifications.index', compact('outlet', 'settings'));
    }

    public function update(Request $request)
    {
        $outlet = current_outlet();
        $settings = $this->notifications->settings($outlet);

        $data = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'notify_new_order' => 'nullable|boolean',
            'notify_order_ready' => 'nullable|boolean',
            'notify_customer_ready' => 'nullable|boolean',
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:64',
            'whatsapp_provider' => 'required|in:webhook,fonnte',
            'whatsapp_webhook_url' => 'nullable|url|max:500',
            'whatsapp_webhook_secret' => 'nullable|string|max:255',
            'whatsapp_fonnte_token' => 'nullable|string|max:255',
            'whatsapp_target' => 'nullable|string|max:64',
        ]);

        $settings->update([
            'is_enabled' => $request->boolean('is_enabled'),
            'notify_new_order' => $request->boolean('notify_new_order'),
            'notify_order_ready' => $request->boolean('notify_order_ready'),
            'notify_customer_ready' => $request->boolean('notify_customer_ready'),
            'telegram_bot_token' => $data['telegram_bot_token'] ?: null,
            'telegram_chat_id' => $data['telegram_chat_id'] ?: null,
            'whatsapp_provider' => $data['whatsapp_provider'],
            'whatsapp_webhook_url' => $data['whatsapp_webhook_url'] ?: null,
            'whatsapp_webhook_secret' => $data['whatsapp_webhook_secret'] ?: null,
            'whatsapp_fonnte_token' => $data['whatsapp_fonnte_token'] ?: null,
            'whatsapp_target' => $data['whatsapp_target'] ?: null,
        ]);

        return back()->with('success', 'Pengaturan notifikasi disimpan.');
    }

    public function test()
    {
        $outlet = current_outlet();
        $settings = $this->notifications->settings($outlet);

        try {
            $this->notifications->sendTest($settings);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pesan test terkirim (cek Telegram / WhatsApp).');
    }
}
