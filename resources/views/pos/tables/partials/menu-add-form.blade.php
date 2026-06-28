<div class="bg-white border rounded-lg p-2 mb-2">
    <div class="flex gap-2 items-center mb-2">
        <div class="flex-1 min-w-0">
            <div class="text-sm font-medium truncate">{{ $item->name }}</div>
            <div class="text-xs text-slate-500">{{ $item->formattedPrice() }}@if($item->hasModifiers()) · varian @endif</div>
        </div>
    </div>
    @include('partials.modifier-picker', [
        'item' => $item,
        'formAction' => route('pos.tables.items.add', $order),
    ])
</div>
