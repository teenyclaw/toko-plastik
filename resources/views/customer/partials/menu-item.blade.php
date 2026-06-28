<div class="bg-white rounded-xl border border-stone-200 overflow-hidden flex gap-3 p-3">
    @if($item->photoUrl())
        <img src="{{ $item->photoUrl() }}" alt="{{ $item->name }}" class="w-20 h-20 object-cover rounded-lg shrink-0">
    @else
        <div class="w-20 h-20 bg-stone-100 rounded-lg shrink-0 flex items-center justify-center text-stone-400 text-xs">No foto</div>
    @endif
    <div class="flex-1 min-w-0">
        <div class="font-medium">{{ $item->name }}</div>
        @if($item->description)
            <div class="text-xs text-stone-500 mt-0.5 line-clamp-2">{{ $item->description }}</div>
        @endif
        <div class="font-semibold text-orange-600 mt-1">{{ $item->formattedPrice() }}</div>
        @if($item->hasModifiers())
            <div class="text-xs text-purple-600 mt-0.5">Tersedia varian</div>
        @endif
        @include('partials.modifier-picker', [
            'item' => $item,
            'formAction' => isset($table)
                ? route('customer.table.cart.add', [$outlet->slug, $table->token])
                : route('customer.cart.add', $outlet->slug),
        ])
    </div>
</div>
