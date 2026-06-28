@extends('customer.layout')

@section('title', 'Bill Meja')

@section('content')
<h1 class="text-xl font-bold mb-1">{{ $table->name }}</h1>
<p class="text-sm text-stone-500 mb-4">Ringkasan pesanan bill terbuka</p>

@if(!$order)
    <div class="text-center py-12 text-stone-500 bg-white rounded-xl border">Belum ada pesanan. Scan menu dan kirim pesanan dari keranjang.</div>
    <a href="{{ route('customer.table.catalog', [$outlet->slug, $table->token]) }}" class="block text-center mt-4 text-orange-600">Lihat menu</a>
@else
    <div class="bg-white rounded-xl border p-4 mb-4">
        <div class="flex justify-between text-sm mb-3">
            <span class="font-mono text-purple-700">{{ $order->order_number }}</span>
            <span class="px-2 py-0.5 rounded text-xs {{ $order->statusEnum()->badgeClass() }}">{{ $order->statusEnum()->label() }}</span>
        </div>
        <div class="space-y-2">
            @foreach($order->items as $item)
                <div class="flex justify-between text-sm border-t border-stone-100 pt-2">
                    <span>{{ $item->item_name }} × {{ $item->qty }}</span>
                    <span>{{ $item->formattedSubtotal() }}</span>
                </div>
            @endforeach
        </div>
        <div class="flex justify-between font-bold text-lg mt-4 pt-3 border-t">
            <span>Total sementara</span>
            <span>{{ $order->formattedTotal() }}</span>
        </div>
    </div>
    <p class="text-xs text-stone-500 text-center">Bayar di kasir saat selesai makan.</p>
    <a href="{{ route('customer.table.catalog', [$outlet->slug, $table->token]) }}" class="block text-center mt-4 bg-orange-500 text-white font-semibold py-3 rounded-xl">Pesan Lagi</a>
@endif
@endsection
