@extends('customer.layout')

@section('title', 'Terima Kasih')

@section('content')
<div class="text-center py-10">
    <div class="text-5xl mb-4">✓</div>
    <h1 class="text-xl font-bold mb-2">Pesanan Diterima!</h1>
    <p class="text-stone-600 mb-4">Nomor pesanan Anda:</p>
    <div class="inline-block bg-orange-100 text-orange-800 font-mono font-bold px-4 py-2 rounded-xl text-lg">{{ $orderNumber }}</div>
    <p class="text-sm text-stone-500 mt-6">Pesanan akan diproses kasir. Silakan tunggu konfirmasi.</p>
    <a href="{{ route('customer.catalog', $outlet->slug) }}" class="inline-block mt-6 text-orange-600 font-medium">Pesan lagi</a>
</div>
@endsection
