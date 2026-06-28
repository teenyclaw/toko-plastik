@extends('customer.layout')

@section('title', 'Bayar QRIS')

@section('content')
<div class="max-w-sm mx-auto text-center">
    <h1 class="text-xl font-bold mb-1">Bayar dengan QRIS</h1>
    <p class="text-sm text-stone-500 mb-1">{{ $order->order_number }}</p>
    <p class="text-lg font-bold text-orange-600 mb-4">{{ $payment->formattedAmount() }}</p>

    @if($payment->qris_url)
        <div class="bg-white rounded-xl border p-4 mb-4 inline-block">
            <img src="{{ $payment->qris_url }}" alt="QRIS" class="w-64 h-64 mx-auto object-contain">
        </div>
    @endif

    <div id="qris-status" class="text-sm text-stone-600 mb-4">
        <span class="inline-flex items-center gap-2 justify-center">
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            Menunggu pembayaran...
        </span>
    </div>

    <p class="text-xs text-stone-400">Scan dengan e-wallet / m-banking. Pesanan dikirim ke dapur setelah lunas.</p>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusUrl = @json(route('customer.qris.status', [$outlet->slug, $order->order_number, $payment]));
    const statusEl = document.getElementById('qris-status');
    let stopped = false;

    async function poll() {
        if (stopped) return;
        try {
            const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.status === 'paid' && data.redirect) {
                stopped = true;
                statusEl.innerHTML = '<span class="text-green-600 font-medium">Pembayaran berhasil!</span>';
                setTimeout(function () { window.location.href = data.redirect; }, 800);
                return;
            }
            if (data.status === 'failed') {
                stopped = true;
                statusEl.innerHTML = '<span class="text-red-600">QR kedaluwarsa. Silakan pesan ulang.</span>';
                return;
            }
        } catch (e) {}
        setTimeout(poll, 3000);
    }

    poll();
});
</script>
@endpush
