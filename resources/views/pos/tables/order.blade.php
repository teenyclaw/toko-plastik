@extends('layouts.app')

@section('title', $order->diningTable->name)

@section('content')
<div class="max-w-4xl">
    <div class="flex items-center justify-between mb-4">
        <div>
            <a href="{{ route('pos.tables.index') }}" class="text-sm text-blue-600">← Daftar meja</a>
            <h1 class="text-2xl font-bold mt-1">{{ $order->diningTable->name }} · Waiter Order</h1>
            <div class="text-sm text-slate-500 font-mono">{{ $order->order_number }}</div>
        </div>
        <div class="text-right">
            <span class="inline-block px-2 py-0.5 rounded text-xs {{ $order->statusEnum()->badgeClass() }}">{{ $order->statusEnum()->label() }}</span>
            <div class="font-bold text-lg mt-1">{{ $order->formattedTotal() }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border p-4">
            <h2 class="font-semibold mb-3">Bill saat ini</h2>
            @if($order->items->isEmpty())
                <p class="text-sm text-slate-500">Belum ada item. Tambah dari menu di samping.</p>
            @else
                <div class="space-y-2">
                    @foreach($order->items as $item)
                        <div class="flex items-center gap-2 py-2 border-t border-slate-100 text-sm">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium">{{ $item->item_name }}</div>
                                <div class="text-slate-500">{{ $item->formattedSubtotal() }}</div>
                                @if($item->is_paid)
                                    <span class="text-xs text-green-600">Lunas</span>
                                @endif
                            </div>
                            @if(!$item->is_paid)
                                <form method="POST" action="{{ route('pos.tables.items.update', [$order, $item]) }}" class="flex items-center gap-1">
                                    @csrf @method('PATCH')
                                    <input type="number" name="qty" value="{{ $item->qty }}" min="1" max="99" class="w-14 border rounded px-2 py-1 text-sm">
                                    <button type="submit" class="text-xs text-blue-600 px-1">✓</button>
                                </form>
                                <form method="POST" action="{{ route('pos.tables.items.remove', [$order, $item]) }}" onsubmit="return confirm('Hapus item?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 px-1">×</button>
                                </form>
                            @else
                                <span class="text-slate-400">×{{ $item->qty }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t">
                <form method="POST" action="{{ route('pos.tables.submit', $order) }}">@csrf
                    <button type="submit" class="text-sm bg-blue-600 text-white px-3 py-2 rounded-lg">Kirim ke Dapur</button>
                </form>
                <form method="POST" action="{{ route('pos.tables.close', $order) }}">@csrf
                    <button type="submit" class="text-sm bg-green-600 text-white px-3 py-2 rounded-lg">Close & Bayar</button>
                </form>
            </div>
        </div>

        <div>
            <h2 class="font-semibold mb-3">Tambah menu</h2>
            <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                @foreach($categories as $category)
                    @if($category->menuItems->isNotEmpty())
                        <div>
                            <div class="text-sm font-medium text-slate-600 mb-2">{{ $category->name }}</div>
                            @foreach($category->menuItems as $item)
                                @include('pos.tables.partials.menu-add-form', ['item' => $item, 'order' => $order])
                            @endforeach
                        </div>
                    @endif
                @endforeach
                @foreach($uncategorized as $item)
                    @include('pos.tables.partials.menu-add-form', ['item' => $item, 'order' => $order])
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
