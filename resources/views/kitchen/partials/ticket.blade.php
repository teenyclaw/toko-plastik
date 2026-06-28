@php
    $order = (object) $order;
@endphp
<article class="rounded-xl border border-zinc-700 bg-zinc-900 p-4" data-order-id="{{ $order->id }}">
    <div class="flex justify-between items-start gap-2 mb-2">
        <div>
            <div class="font-mono font-bold text-lg">{{ $order->order_number }}</div>
            <div class="font-medium text-zinc-100">{{ $order->customer_label }}</div>
            <div class="text-xs text-zinc-500">{{ $order->source_label }} · {{ $order->created_at }} · {{ $order->wait_minutes }} mnt</div>
        </div>
    </div>

    @if($order->notes)
        <div class="text-xs bg-zinc-800 rounded px-2 py-1 mb-2 text-amber-200">Catatan: {{ $order->notes }}</div>
    @endif

    <ul class="text-sm space-y-1 mb-4">
        @foreach($order->items as $item)
            <li class="flex justify-between gap-2 border-t border-zinc-800 pt-1">
                <span><strong>{{ $item['qty'] }}×</strong> {{ $item['name'] }}
                    @if(!empty($item['modifiers']))<span class="text-zinc-500 text-xs block">{{ $item['modifiers'] }}</span>@endif
                    @if(!empty($item['note']))<span class="text-zinc-500 text-xs block">{{ $item['note'] }}</span>@endif
                </span>
            </li>
        @endforeach
    </ul>

    <div class="flex flex-wrap gap-2">
        @if($column === 'pending')
            <form method="POST" action="{{ url('/kitchen/orders/'.$order->id.'/start') }}">
                @csrf
                <button type="submit" class="text-sm bg-orange-600 hover:bg-orange-500 text-white px-3 py-2 rounded-lg font-medium">Mulai Masak</button>
            </form>
        @endif
        @if($column === 'cooking')
            <form method="POST" action="{{ url('/kitchen/orders/'.$order->id.'/ready') }}">
                @csrf
                <button type="submit" class="text-sm bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-2 rounded-lg font-medium">Siap Saji</button>
            </form>
        @endif
        @if($column === 'ready')
            <span class="text-xs text-emerald-400 py-2">Menunggu pelayan...</span>
        @endif
    </div>
</article>
