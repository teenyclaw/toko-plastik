@extends('layouts.app')

@section('title', 'Bayar QRIS')

@section('content')
<div class="max-w-sm mx-auto text-center">
    <a href="{{ route('pos.payment', $order) }}" class="text-sm text-blue-600 mb-4 inline-block">← Ganti metode bayar</a>
    <h1 class="text-xl font-bold mb-1">Scan QRIS</h1>
    <p class="text-sm text-slate-500 mb-4">{{ $order->order_number }} · {{ $payment->formattedAmount() }}</p>

    @if($payment->qris_url)
        <div class="bg-white rounded-xl border p-4 mb-4 inline-block">
            <img src="{{ $payment->qris_url }}" alt="QRIS" class="w-64 h-64 mx-auto object-contain">
        </div>
    @else
        <div class="bg-red-50 text-red-700 rounded-xl p-4 mb-4 text-sm">QR code tidak tersedia. Coba buat ulang dari halaman pembayaran.</div>
    @endif

    <div id="qris-status" class="text-sm text-slate-600 mb-4">
        <span class="inline-flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            Menunggu pembayaran...
        </span>
    </div>

    <p class="text-xs text-slate-400">QR berlaku 15 menit. Halaman akan refresh otomatis setelah bayar.</p>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const statusUrl = @json(route('pos.qris.status', [$order, $payment]));
    const statusEl = document.getElementById('qris-status');
    let stopped = false;

    async function poll() {
        if (stopped) return;
        try {
            const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.status === 'paid' && data.redirect) {
                stopped = true;
                statusEl.innerHTML = '<span class="text-green-600 font-medium">Pembayaran berhasil! Mencetak struk...</span>';
                window.location.href = data.redirect;
                return;
            }
            if (data.status === 'failed') {
                stopped = true;
                statusEl.innerHTML = '<span class="text-red-600">' + (data.message || 'Pembayaran gagal.') + '</span>';
                return;
            }
        } catch (e) {}
        setTimeout(poll, 3000);
    }

    poll();
})();
</script>
@endpush
