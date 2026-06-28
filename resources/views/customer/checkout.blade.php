@extends('customer.layout')

@section('title', 'Checkout')

@section('content')
<h1 class="text-xl font-bold mb-4">Checkout</h1>

<div class="bg-white rounded-xl border p-4 mb-4 text-sm space-y-1">
    @foreach($cart as $line)
        <div class="flex justify-between">
            <span>{{ $line['qty'] }}× {{ $line['display_name'] ?? $line['name'] }}</span>
            <span>Rp {{ number_format($line['price'] * $line['qty'], 0, ',', '.') }}</span>
        </div>
    @endforeach
    <div class="border-t pt-2 mt-2 flex justify-between font-bold">
        <span>Total</span>
        <span id="checkout-total">Rp {{ number_format($total, 0, ',', '.') }}</span>
    </div>
</div>

<form method="POST" action="{{ route('customer.checkout.store', $outlet->slug) }}" class="space-y-3" id="checkout-form"
    @if($loyaltySettings)
    x-data="{
        points: {{ old('loyalty_points', 0) }},
        balance: 0,
        maxRedeem: 0,
        minRedeem: {{ $loyaltySettings->min_redeem_points }},
        rpPerPoint: {{ $loyaltySettings->redeem_rp_per_point }},
        total: {{ $total }},
        loading: false,
        message: '',
        async lookup() {
            const phone = this.$refs.phone.value;
            if (!phone || phone.length < 10) { this.balance = 0; this.maxRedeem = 0; this.message = ''; return; }
            this.loading = true;
            try {
                const res = await fetch('{{ route('customer.checkout.loyalty', $outlet->slug) }}?phone=' + encodeURIComponent(phone));
                const data = await res.json();
                if (!data.enabled) return;
                if (!data.eligible) { this.message = data.message; this.balance = 0; return; }
                this.balance = data.points;
                this.maxRedeem = data.max_redeem;
                this.minRedeem = data.min_redeem;
                this.rpPerPoint = data.rp_per_point;
                this.message = this.balance > 0 ? 'Saldo: ' + this.balance.toLocaleString('id-ID') + ' poin' : 'Belum ada poin. Dapat poin setelah bayar.';
            } finally { this.loading = false; }
        },
        get discount() { return Math.min(this.points, this.maxRedeem) * this.rpPerPoint; },
        get netTotal() { return Math.max(0, this.total - this.discount); }
    }"
    @endif
>
    @csrf
    <div>
        <label class="block text-sm font-medium mb-1">Nama *</label>
        <input type="text" name="customer_name" value="{{ old('customer_name') }}" required class="w-full border rounded-xl px-3 py-2">
        @error('customer_name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">No. WhatsApp / Telepon *</label>
        <input type="tel" name="customer_phone" x-ref="phone" value="{{ old('customer_phone') }}"
            @if($loyaltySettings) @blur="lookup()" @endif
            required class="w-full border rounded-xl px-3 py-2">
        @error('customer_phone')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        @if($loyaltySettings)
            <p class="text-xs text-slate-500 mt-1" x-show="message" x-text="message"></p>
        @endif
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Catatan pesanan</label>
        <textarea name="notes" rows="2" class="w-full border rounded-xl px-3 py-2">{{ old('notes') }}</textarea>
    </div>

    @if($loyaltySettings)
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm" x-show="balance >= minRedeem">
            <label class="block font-medium mb-1">Pakai poin (QRIS saja)</label>
            <div class="flex gap-2 items-center">
                <input type="number" name="loyalty_points" x-model.number="points" :min="0" :max="maxRedeem" class="w-28 border rounded-lg px-3 py-2">
                <span class="text-xs text-slate-600">Maks. <span x-text="maxRedeem"></span> poin · hemat Rp <span x-text="discount.toLocaleString('id-ID')"></span></span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Bayar di kasir? Redeem lewat kasir saat pembayaran.</p>
        </div>
        <div class="text-sm font-medium flex justify-between" x-show="discount > 0">
            <span>Total setelah poin</span>
            <span x-text="'Rp ' + netTotal.toLocaleString('id-ID')"></span>
        </div>
    @endif

    @if($qrisEnabled ?? false)
        <div>
            <label class="block text-sm font-medium mb-2">Cara bayar</label>
            <div class="space-y-2">
                <label class="flex items-center gap-2 border rounded-xl px-3 py-2 cursor-pointer">
                    <input type="radio" name="pay_method" value="counter" checked> Bayar di kasir (pesan dulu)
                </label>
                <label class="flex items-center gap-2 border rounded-xl px-3 py-2 cursor-pointer">
                    <input type="radio" name="pay_method" value="qris"> Bayar QRIS sekarang (scan QR)
                </label>
            </div>
        </div>
    @endif

    <button type="submit" class="w-full bg-orange-500 text-white font-semibold py-3 rounded-xl">Kirim Pesanan</button>
</form>
@endsection
