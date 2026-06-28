@extends('layouts.app')

@section('title', 'Antrian Kasir')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Antrian Pesanan</h1>
    <div class="flex items-center gap-3 text-sm">
        <span class="inline-flex items-center gap-1.5 text-slate-600">
            <span data-realtime-indicator class="w-2 h-2 rounded-full bg-slate-300"></span>
            Live
        </span>
        <a href="{{ route('kitchen.display') }}" target="_blank" class="text-orange-700">Layar Dapur (KDS) ↗</a>
        <a href="{{ route('pos.tables.index') }}" class="text-purple-700">Meja / Open Bill</a>
    </div>
</div>

<div id="order-list" class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
    @forelse($orders as $order)
        @include('pos.partials.order-card', ['order' => $order])
    @empty
        <div id="empty-state" class="col-span-full text-center py-16 text-slate-500 bg-white rounded-xl border">Tidak ada pesanan menunggu.</div>
    @endforelse
</div>
@endsection

@push('scripts')
    @include('partials.realtime-client', ['realtimeMode' => 'pos'])
@endpush
