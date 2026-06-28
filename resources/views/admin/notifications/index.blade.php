@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<h1 class="text-2xl font-bold mb-6">Notifikasi Order</h1>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <form method="POST" action="{{ route('admin.notifications.update') }}" class="bg-white rounded-xl border p-5 space-y-5">
            @csrf @method('PUT')

            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" name="is_enabled" value="1" @checked($settings->is_enabled)> Notifikasi aktif
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="notify_new_order" value="1" @checked($settings->notify_new_order)> Pesanan baru (antrian)
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="notify_order_ready" value="1" @checked($settings->notify_order_ready)> Pesanan siap (staff)
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="notify_customer_ready" value="1" @checked($settings->notify_customer_ready)> Siap → WA pelanggan (Fonnte)
                </label>
            </div>

            <div class="border-t pt-5">
                <h2 class="font-semibold mb-3">Telegram</h2>
                <p class="text-xs text-slate-500 mb-3">Buat bot via @BotFather, tambahkan ke grup/channel, dapatkan <code>chat_id</code>.</p>
                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm text-slate-600">Bot token</label>
                        <input type="text" name="telegram_bot_token" value="{{ old('telegram_bot_token', $settings->telegram_bot_token) }}" placeholder="123456:ABC..." class="w-full border rounded-lg px-3 py-2 mt-1 text-sm">
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">Chat ID</label>
                        <input type="text" name="telegram_chat_id" value="{{ old('telegram_chat_id', $settings->telegram_chat_id) }}" placeholder="-1001234567890" class="w-full border rounded-lg px-3 py-2 mt-1 text-sm">
                    </div>
                </div>
            </div>

            <div class="border-t pt-5">
                <h2 class="font-semibold mb-3">WhatsApp</h2>
                <div class="mb-3">
                    <label class="text-sm text-slate-600">Provider</label>
                    <select name="whatsapp_provider" class="w-full md:w-64 border rounded-lg px-3 py-2 mt-1 text-sm">
                        <option value="webhook" @selected($settings->whatsapp_provider === 'webhook')>Webhook generik</option>
                        <option value="fonnte" @selected($settings->whatsapp_provider === 'fonnte')>Fonnte API</option>
                    </select>
                </div>
                <div class="grid md:grid-cols-2 gap-3">
                    <div class="md:col-span-2 webhook-fields">
                        <label class="text-sm text-slate-600">Webhook URL</label>
                        <input type="url" name="whatsapp_webhook_url" value="{{ old('whatsapp_webhook_url', $settings->whatsapp_webhook_url) }}" placeholder="https://..." class="w-full border rounded-lg px-3 py-2 mt-1 text-sm">
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">Bearer / secret (opsional)</label>
                        <input type="text" name="whatsapp_webhook_secret" value="{{ old('whatsapp_webhook_secret', $settings->whatsapp_webhook_secret) }}" class="w-full border rounded-lg px-3 py-2 mt-1 text-sm">
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">Fonnte token</label>
                        <input type="text" name="whatsapp_fonnte_token" value="{{ old('whatsapp_fonnte_token', $settings->whatsapp_fonnte_token) }}" class="w-full border rounded-lg px-3 py-2 mt-1 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm text-slate-600">Target (grup/nomor staff)</label>
                        <input type="text" name="whatsapp_target" value="{{ old('whatsapp_target', $settings->whatsapp_target) }}" placeholder="62812... atau group id Fonnte" class="w-full border rounded-lg px-3 py-2 mt-1 text-sm">
                    </div>
                </div>
            </div>

            <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm">Simpan</button>
        </form>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl border p-5 text-sm">
            <h2 class="font-semibold mb-2">Kirim test</h2>
            <p class="text-slate-500 mb-4">Pastikan notifikasi aktif dan minimal satu channel terisi.</p>
            <form method="POST" action="{{ route('admin.notifications.test') }}">
                @csrf
                <button type="submit" class="w-full bg-emerald-600 text-white py-2 rounded-lg font-medium">Kirim pesan test</button>
            </form>
        </div>
        <div class="bg-slate-50 rounded-xl border p-4 text-xs text-slate-600 space-y-2">
            <p><strong>Queue:</strong> Notifikasi dikirim via job queue. Jalankan <code>php artisan queue:work</code> di production, atau set <code>QUEUE_CONNECTION=sync</code> untuk development.</p>
            <p><strong>Pesanan baru</strong> — QR checkout & bill meja dikirim ke dapur.</p>
            <p><strong>Siap disajikan</strong> — saat KDS tandai ready.</p>
        </div>
    </div>
</div>
@endsection
