@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
<div class="max-w-lg">
    <a href="{{ route('pos.orders.show', $order) }}" class="text-sm text-blue-600 mb-4 inline-block">← Kembali</a>
    <h1 class="text-xl font-bold mb-1">Pembayaran — {{ $order->order_number }}</h1>
    @if($order->dining_table_id)
        <p class="text-sm text-slate-500 mb-4">Split bill: centang item yang dibayar sekarang, atau bayar semua sekaligus.</p>
    @endif

    @php
        $unpaidItems = $order->items->where('is_paid', false);
        $remaining = $order->remainingBalance();
        $splitSelected = $unpaidItems->pluck('id')->values();
        $splitItems = $unpaidItems->map(fn ($i) => ['id' => $i->id, 'subtotal' => $i->subtotal()])->values();
    @endphp

    <div class="bg-white rounded-xl border p-5 mb-4">
        <div class="flex justify-between items-center mb-4">
            <span class="text-sm text-slate-500">Sisa tagihan</span>
            <span class="text-2xl font-bold">{{ $order->formattedRemainingBalance() }}</span>
        </div>

        <form method="POST" action="{{ route('pos.payment.store', $order) }}" class="space-y-4" x-data='{
            selected: @json($splitSelected),
            items: @json($splitItems),
            loyaltyPoints: 0,
            rpPerPoint: {{ $loyaltySettings->redeem_rp_per_point ?? 0 }},
            memberPoints: {{ $member->points ?? 0 }},
            minRedeem: {{ $loyaltySettings->min_redeem_points ?? 0 }},
            get subtotal() {
                return this.items.filter(i => this.selected.includes(i.id)).reduce((s, i) => s + i.subtotal, 0);
            },
            get maxRedeem() {
                if (!this.memberPoints || !this.rpPerPoint) return 0;
                const cap = Math.floor(this.subtotal * {{ $loyaltySettings->max_redeem_percent ?? 0 }} / 100);
                return Math.min(this.memberPoints, Math.floor(cap / this.rpPerPoint));
            },
            get discount() {
                return Math.min(this.loyaltyPoints, this.maxRedeem) * this.rpPerPoint;
            },
            get netTotal() {
                return Math.max(0, this.subtotal - this.discount);
            }
        }'>
            @csrf

            @if($unpaidItems->count() > 1)
                <div class="border rounded-lg divide-y max-h-64 overflow-y-auto">
                    @foreach($unpaidItems as $item)
                        <label class="flex items-start gap-3 p-3 cursor-pointer hover:bg-slate-50">
                            <input type="checkbox" name="item_ids[]" value="{{ $item->id }}" x-model.number="selected" class="mt-1">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium">{{ $item->item_name }}</div>
                                @if($item->modifierSummary())
                                    <div class="text-xs text-purple-600">{{ $item->modifierSummary() }}</div>
                                @endif
                                <div class="text-xs text-slate-500">{{ $item->qty }} × Rp {{ number_format($item->price, 0, ',', '.') }}</div>
                            </div>
                            <div class="text-sm font-semibold">{{ $item->formattedSubtotal() }}</div>
                        </label>
                    @endforeach
                </div>
                <div class="flex justify-between text-sm font-medium">
                    <span>Subtotal terpilih</span>
                    <span x-text="'Rp ' + subtotal.toLocaleString('id-ID')"></span>
                </div>
            @else
                @foreach($unpaidItems as $item)
                    <input type="hidden" name="item_ids[]" value="{{ $item->id }}">
                    <div class="text-sm border rounded-lg p-3">
                        <div class="font-medium">{{ $item->item_name }} × {{ $item->qty }}</div>
                        <div class="text-right font-bold mt-1">{{ $item->formattedSubtotal() }}</div>
                    </div>
                @endforeach
            @endif

            @if($unpaidItems->count() > 1)
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="pay_all" value="1" @change="if($event.target.checked) selected = items.map(i => i.id)">
                    Pilih semua item
                </label>
            @endif

            @if($loyaltySettings && $member)
                <div class="border rounded-lg p-3 bg-amber-50 text-sm space-y-2">
                    <div class="font-medium">Member: {{ $member->displayName() }} · {{ number_format($member->points) }} poin</div>
                    <div class="flex flex-wrap gap-2 items-center">
                        <label class="text-slate-600">Redeem poin</label>
                        <input type="number" name="loyalty_points" x-model.number="loyaltyPoints" min="0" :max="maxRedeem" class="w-24 border rounded px-2 py-1">
                        <span class="text-xs text-slate-500">Maks. <span x-text="maxRedeem"></span> · diskon Rp <span x-text="discount.toLocaleString('id-ID')"></span></span>
                    </div>
                </div>
            @elseif($loyaltySettings && $canEarnLoyalty && ! $member)
                <div class="text-xs text-slate-500 border rounded-lg p-3">Member baru — poin akan terkumpul setelah pembayaran.</div>
            @endif

            <div class="flex justify-between text-sm font-medium" x-show="discount > 0">
                <span>Total bayar</span>
                <span x-text="'Rp ' + netTotal.toLocaleString('id-ID')"></span>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Metode pembayaran</label>
                <div class="flex flex-wrap gap-3">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="payment_method" value="cash" checked
                            onchange="document.getElementById('cash-fields').classList.remove('hidden')"> Tunai
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="payment_method" value="transfer"
                            onchange="document.getElementById('cash-fields').classList.add('hidden')"> Transfer
                    </label>
                    @if($qrisEnabled ?? false)
                        <label class="flex items-center gap-2">
                            <input type="radio" name="payment_method" value="qris"
                                onchange="document.getElementById('cash-fields').classList.add('hidden')"> QRIS
                        </label>
                    @endif
                </div>
            </div>
            <div id="cash-fields">
                <label class="block text-sm mb-1">Nominal bayar (Rp)</label>
                <input type="number" name="amount_paid" :min="netTotal" :value="netTotal" class="w-full border rounded-lg px-3 py-2">
                @error('amount_paid')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-xl font-semibold" :disabled="selected.length === 0">
                <span x-text="selected.length === items.length || items.length <= 1 ? 'Proses Pembayaran' : 'Bayar Item Terpilih'"></span>
            </button>
        </form>
    </div>

    @if($order->payments->isNotEmpty())
        <div class="text-sm text-slate-500">
            <p class="font-medium text-slate-700 mb-2">Pembayaran sebelumnya</p>
            @foreach($order->payments as $prev)
                <div class="flex justify-between py-1 border-t">
                    <span>{{ $prev->paid_at->format('d/m H:i') }} · {{ $prev->payment_method }}</span>
                    <a href="{{ route('pos.receipt', [$order, $prev]) }}" class="text-blue-600">{{ $prev->formattedAmount() }}</a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
