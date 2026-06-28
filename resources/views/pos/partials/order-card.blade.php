<a href="{{ route('pos.orders.show', $order) }}" class="block bg-white rounded-xl border p-4 hover:border-amber-400 transition">
    <div class="flex justify-between items-start mb-2">
        <span class="font-mono font-bold text-amber-700">{{ $order->order_number }}</span>
        <span class="text-xs text-slate-500">{{ $order->created_at?->format('d/m/Y H:i') }}</span>
    </div>
    <div class="font-medium">{{ $order->displayCustomer() }}</div>
    <div class="flex items-center gap-2 mt-0.5">
        <span class="text-xs px-1.5 py-0.5 rounded {{ $order->statusEnum()->badgeClass() }}">{{ $order->statusEnum()->label() }}</span>
        @if($order->diningTable)
            <span class="text-xs text-purple-600">{{ $order->sourceEnum()->label() }}</span>
        @else
            <span class="text-xs text-slate-500">{{ $order->customer_phone }}</span>
        @endif
    </div>
    <div class="mt-3 flex justify-between text-sm">
        <span>{{ $order->items->sum('qty') }} item</span>
        <span class="font-bold">{{ $order->formattedTotal() }}</span>
    </div>
</a>
