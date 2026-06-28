@extends('layouts.app')

@section('title', 'Produk')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Produk</h1>
            <p class="text-sm text-slate-600 mt-1">Master data barang jual.</p>
        </div>
        <a href="{{ route('products.create') }}" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm hover:bg-blue-800">+ Tambah Produk</a>
    </div>

    <form method="GET" class="mb-4 flex gap-2">
        <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama, kode, barcode..."
               class="border rounded-lg px-3 py-2 text-sm w-full max-w-md">
        <button type="submit" class="px-4 py-2 border rounded-lg text-sm bg-white">Cari</button>
    </form>

    <div class="bg-white rounded-xl border shadow-sm overflow-x-auto">
        <table class="w-full text-sm min-w-[800px]">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3">Harga Jual</th>
                    <th class="px-4 py-3">Stok</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 w-24"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($products as $product)
                    <tr class="{{ $product->isLowStock() ? 'bg-amber-50' : '' }}">
                        <td class="px-4 py-3 font-mono text-xs">{{ $product->code }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $product->name }}</div>
                            @if ($product->barcode)
                                <div class="text-xs text-slate-500">{{ $product->barcode }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $product->category->name }}</td>
                        <td class="px-4 py-3">{{ format_rupiah($product->sell_price) }}</td>
                        <td class="px-4 py-3">
                            {{ format_qty($product->stock) }} {{ $product->unit->abbreviation }}
                            @if ($product->isLowStock())
                                <span class="text-xs text-amber-700">(min {{ format_qty($product->min_stock) }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($product->is_active)
                                <span class="text-green-700">Aktif</span>
                            @else
                                <span class="text-slate-500">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('products.edit', $product) }}" class="text-blue-700 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
