@extends('layouts.app')

@section('title', ($product->exists ? 'Edit' : 'Tambah') . ' Produk')

@section('content')
    <h1 class="text-2xl font-bold mb-6">{{ $product->exists ? 'Edit' : 'Tambah' }} Produk</h1>

    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
          class="max-w-2xl bg-white rounded-xl border p-6 grid gap-4 sm:grid-cols-2">
        @csrf
        @if ($product->exists) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium mb-1">Kode</label>
            <input type="text" name="code" value="{{ old('code', $product->code) }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
            @error('code')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Barcode</label>
            <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium mb-1">Nama Produk</label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Kategori</label>
            <select name="category_id" required class="w-full border rounded-lg px-3 py-2 text-sm">
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Satuan</label>
            <select name="unit_id" required class="w-full border rounded-lg px-3 py-2 text-sm">
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" @selected(old('unit_id', $product->unit_id) == $unit->id)>{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Supplier (opsional)</label>
            <select name="supplier_id" class="w-full border rounded-lg px-3 py-2 text-sm">
                <option value="">— Tidak ada —</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', $product->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Harga Beli</label>
            <input type="number" name="buy_price" min="0" step="1" value="{{ old('buy_price', $product->buy_price ?? 0) }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Harga Jual</label>
            <input type="number" name="sell_price" min="0" step="1" value="{{ old('sell_price', $product->sell_price ?? 0) }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Stok Awal</label>
            <input type="number" name="stock" min="0" step="0.001" value="{{ old('stock', $product->stock ?? 0) }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Stok Minimum</label>
            <input type="number" name="min_stock" min="0" step="0.001" value="{{ old('min_stock', $product->min_stock ?? 0) }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="sm:col-span-2">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))>
                Produk aktif (tampil di POS)
            </label>
        </div>

        <div class="sm:col-span-2 flex gap-2 pt-2">
            <button type="submit" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm">Simpan</button>
            <a href="{{ route('products.index') }}" class="px-4 py-2 border rounded-lg text-sm">Batal</a>
        </div>
    </form>
@endsection
