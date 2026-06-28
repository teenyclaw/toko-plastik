@extends('layouts.app')

@section('title', 'Stok')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Manajemen Stok</h1>
        <p class="text-sm text-slate-600 mt-1">Monitor stok, penyesuaian, dan riwayat pergerakan.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        <div class="bg-white rounded-xl border p-4">
            <p class="text-sm text-slate-500">Total Produk</p>
            <p class="text-2xl font-bold">{{ $stats['total_products'] }}</p>
        </div>
        <div class="bg-white rounded-xl border p-4">
            <p class="text-sm text-slate-500">Stok Menipis</p>
            <p class="text-2xl font-bold text-amber-600">{{ $stats['low_stock'] }}</p>
        </div>
        <div class="bg-white rounded-xl border p-4">
            <p class="text-sm text-slate-500">Stok Habis</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['out_of_stock'] }}</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="flex flex-wrap gap-2 items-center">
                <form method="GET" class="flex gap-2 flex-1">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari produk..."
                           class="border rounded-lg px-3 py-2 text-sm flex-1 max-w-xs">
                    <select name="filter" class="border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
                        <option value="">Semua stok</option>
                        <option value="low" @selected($filter === 'low')>Stok menipis saja</option>
                    </select>
                    <button type="submit" class="px-3 py-2 border rounded-lg text-sm bg-white">Filter</button>
                </form>
            </div>

            <div class="bg-white rounded-xl border overflow-x-auto">
                <table class="w-full text-sm min-w-[500px]">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3">Stok</th>
                            <th class="px-4 py-3">Min</th>
                            <th class="px-4 py-3 w-28"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($products as $product)
                            <tr class="{{ $product->isLowStock() ? 'bg-amber-50' : '' }}" x-data="{ open: false }">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $product->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $product->code }}</div>
                                </td>
                                <td class="px-4 py-3 font-medium">{{ format_qty($product->stock) }} {{ $product->unit->abbreviation }}</td>
                                <td class="px-4 py-3">{{ format_qty($product->min_stock) }}</td>
                                <td class="px-4 py-3">
                                    <button type="button" @click="open = !open" class="text-blue-700 text-xs hover:underline">Sesuaikan</button>
                                    <form method="POST" action="{{ route('stock.adjust') }}" x-show="open" x-cloak class="mt-2 space-y-2 border rounded-lg p-2 bg-slate-50">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <select name="mode" class="w-full border rounded text-xs px-2 py-1">
                                            <option value="in">Stok masuk (+)</option>
                                            <option value="out">Stok keluar (−)</option>
                                            <option value="set">Set stok (opname)</option>
                                        </select>
                                        <input type="number" name="quantity" step="0.001" min="0.001" placeholder="Qty" class="w-full border rounded text-xs px-2 py-1">
                                        <input type="number" name="new_stock" step="0.001" min="0" placeholder="Stok baru (opname)" class="w-full border rounded text-xs px-2 py-1">
                                        <input type="text" name="notes" placeholder="Catatan" class="w-full border rounded text-xs px-2 py-1">
                                        <button type="submit" class="w-full bg-blue-700 text-white text-xs py-1 rounded">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $products->links() }}
        </div>

        <div>
            <h2 class="font-semibold mb-3">Riwayat Pergerakan</h2>
            <div class="bg-white rounded-xl border divide-y text-sm max-h-[600px] overflow-y-auto">
                @forelse ($movements as $movement)
                    <div class="p-3">
                        <div class="font-medium text-xs">{{ $movement->product->name }}</div>
                        <div class="flex justify-between mt-1">
                            <span class="text-xs px-1.5 py-0.5 rounded bg-slate-100">{{ $movement->type->label() }}</span>
                            <span class="text-xs text-slate-500">{{ $movement->created_at->format('d/m H:i') }}</span>
                        </div>
                        <div class="text-xs mt-1 text-slate-600">
                            {{ format_qty($movement->stock_before) }} → <strong>{{ format_qty($movement->stock_after) }}</strong>
                            ({{ format_qty($movement->quantity) }})
                        </div>
                        @if ($movement->notes)
                            <div class="text-xs text-slate-500 mt-0.5">{{ $movement->notes }}</div>
                        @endif
                        <div class="text-[10px] text-slate-400 mt-0.5">{{ $movement->user->name }}</div>
                    </div>
                @empty
                    <p class="p-4 text-slate-500 text-sm">Belum ada pergerakan stok.</p>
                @endforelse
            </div>
            {{ $movements->onEachSide(1)->links() }}
        </div>
    </div>
@endsection
