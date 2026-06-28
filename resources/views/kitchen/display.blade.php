@extends('layouts.kitchen')

@section('title', 'Layar Dapur')

@section('content')
<div class="p-4">
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-zinc-400 inline-flex items-center gap-2">
            <span data-realtime-indicator class="w-2 h-2 rounded-full bg-zinc-600"></span>
            Live · update otomatis saat ada order baru
        </p>
        <div class="flex gap-3 text-xs">
            <span class="px-2 py-1 rounded bg-amber-900/60 text-amber-200">Antrian: <strong id="count-pending">{{ $columns['counts']['pending'] }}</strong></span>
            <span class="px-2 py-1 rounded bg-orange-900/60 text-orange-200">Masak: <strong id="count-cooking">{{ $columns['counts']['cooking'] }}</strong></span>
            <span class="px-2 py-1 rounded bg-emerald-900/60 text-emerald-200">Siap: <strong id="count-ready">{{ $columns['counts']['ready'] }}</strong></span>
        </div>
    </div>

    <div id="kitchen-board" class="grid lg:grid-cols-3 gap-4">
        <section class="rounded-xl border border-zinc-800 bg-zinc-900/50 min-h-[60vh]">
            <div class="px-4 py-3 border-b border-zinc-800 font-semibold text-amber-400">Antrian Baru</div>
            <div id="column-pending" class="p-3 space-y-3">
                @forelse($columns['columns']['pending'] as $order)
                    @include('kitchen.partials.ticket', ['order' => $order, 'column' => 'pending'])
                @empty
                    <p class="text-sm text-zinc-600 text-center py-8">Kosong</p>
                @endforelse
            </div>
        </section>
        <section class="rounded-xl border border-zinc-800 bg-zinc-900/50 min-h-[60vh]">
            <div class="px-4 py-3 border-b border-zinc-800 font-semibold text-orange-400">Sedang Dimasak</div>
            <div id="column-cooking" class="p-3 space-y-3">
                @forelse($columns['columns']['cooking'] as $order)
                    @include('kitchen.partials.ticket', ['order' => $order, 'column' => 'cooking'])
                @empty
                    <p class="text-sm text-zinc-600 text-center py-8">Kosong</p>
                @endforelse
            </div>
        </section>
        <section class="rounded-xl border border-zinc-800 bg-zinc-900/50 min-h-[60vh]">
            <div class="px-4 py-3 border-b border-zinc-800 font-semibold text-emerald-400">Siap Disajikan</div>
            <div id="column-ready" class="p-3 space-y-3">
                @forelse($columns['columns']['ready'] as $order)
                    @include('kitchen.partials.ticket', ['order' => $order, 'column' => 'ready'])
                @empty
                    <p class="text-sm text-zinc-600 text-center py-8">Kosong</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
    @include('partials.realtime-client', ['realtimeMode' => 'kitchen'])
@endpush
