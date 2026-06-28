@extends('customer.layout')

@section('title', 'Keranjang')

@section('content')
<h1 class="text-xl font-bold mb-4">Keranjang</h1>

@if(empty($cart))
    <p class="text-stone-500 text-center py-8">Keranjang masih kosong.</p>
    <a href="{{ isset($table) ? route('customer.table.catalog', [$outlet->slug, $table->token]) : route('customer.catalog', $outlet->slug) }}" class="block text-center text-orange-600">Kembali ke menu</a>
@else
    <div class="space-y-3 mb-4">
        @foreach($cart as $key => $line)
            @php $lineKey = $line['line_key'] ?? $key; @endphp
            <div class="bg-white rounded-xl border p-3">
                <div class="flex justify-between gap-2">
                    <div>
                        <div class="font-medium">{{ $line['display_name'] ?? $line['name'] }}</div>
                        <div class="text-sm text-stone-500">Rp {{ number_format($line['price'], 0, ',', '.') }} × {{ $line['qty'] }}</div>
                        @if(!empty($line['note']))
                            <div class="text-xs text-stone-400 mt-1">Catatan: {{ $line['note'] }}</div>
                        @endif
                    </div>
                    <div class="font-semibold">Rp {{ number_format($line['price'] * $line['qty'], 0, ',', '.') }}</div>
                </div>
                <div class="flex gap-2 mt-2">
                    <form method="POST" action="{{ isset($table) ? route('customer.table.cart.update', [$outlet->slug, $table->token, $lineKey]) : route('customer.cart.update', [$outlet->slug, $lineKey]) }}" class="flex gap-2 items-center">
                        @csrf @method('PATCH')
                        <input type="number" name="qty" value="{{ $line['qty'] }}" min="0" max="99" class="w-16 border rounded px-2 py-1 text-sm">
                        <button type="submit" class="text-xs text-blue-600">Update</button>
                    </form>
                    <form method="POST" action="{{ isset($table) ? route('customer.table.cart.remove', [$outlet->slug, $table->token, $lineKey]) : route('customer.cart.remove', [$outlet->slug, $lineKey]) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-600">Hapus</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border p-4 mb-4">
        <div class="flex justify-between font-bold text-lg">
            <span>Total</span>
            <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
        </div>
    </div>

    @if(isset($table))
        <form method="POST" action="{{ route('customer.table.order.submit', [$outlet->slug, $table->token]) }}">
            @csrf
            <button type="submit" class="block w-full text-center bg-orange-500 text-white font-semibold py-3 rounded-xl">Kirim Pesanan ke Dapur</button>
        </form>
        <p class="text-xs text-stone-500 text-center mt-2">Pesanan ditambahkan ke bill {{ $table->name }}. Anda bisa pesan lagi setelah ini.</p>
    @else
        <a href="{{ route('customer.checkout', $outlet->slug) }}" class="block w-full text-center bg-orange-500 text-white font-semibold py-3 rounded-xl">Checkout</a>
    @endif
@endif
@endsection
