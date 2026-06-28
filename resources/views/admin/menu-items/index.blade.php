@extends('layouts.app')

@section('title', 'Menu')

@section('content')
<h1 class="text-2xl font-bold mb-6">Kelola Menu</h1>

<form method="POST" action="{{ route('admin.menu-items.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl border p-4 mb-6 grid md:grid-cols-3 gap-3">
    @csrf
    <div><label class="text-sm">Nama *</label><input type="text" name="name" required class="w-full border rounded-lg px-3 py-2 mt-1"></div>
    <div><label class="text-sm">Kategori</label>
        <select name="category_id" class="w-full border rounded-lg px-3 py-2 mt-1">
            <option value="">— Tanpa kategori —</option>
            @foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach
        </select>
    </div>
    <div><label class="text-sm">Harga (Rp) *</label><input type="number" name="price" required min="0" class="w-full border rounded-lg px-3 py-2 mt-1"></div>
    <div class="md:col-span-2"><label class="text-sm">Deskripsi</label><input type="text" name="description" class="w-full border rounded-lg px-3 py-2 mt-1"></div>
    <div><label class="text-sm">Foto</label><input type="file" name="photo" accept="image/*" class="w-full mt-1 text-sm"></div>
    <div><label class="inline-flex items-center gap-2 text-sm mt-6"><input type="checkbox" name="is_available" value="1" checked> Tersedia</label></div>
    <div class="md:col-span-3 border-t pt-3 grid md:grid-cols-3 gap-3">
        <div><label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="track_stock" value="1"> Lacak stok</label></div>
        <div><label class="text-sm">Stok awal</label><input type="number" name="stock_qty" min="0" value="0" class="w-full border rounded-lg px-3 py-2 mt-1"></div>
        <div><label class="text-sm">Batas stok menipis</label><input type="number" name="low_stock_threshold" min="0" value="5" class="w-full border rounded-lg px-3 py-2 mt-1"></div>
    </div>
    <div class="md:col-span-3"><button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-lg">Tambah Menu</button></div>
</form>

<div class="grid md:grid-cols-2 gap-4">
    @foreach($items as $item)
        <div class="bg-white rounded-xl border p-4 flex gap-3">
            @if($item->photoUrl())
                <img src="{{ $item->photoUrl() }}" class="w-16 h-16 object-cover rounded-lg">
            @endif
            <div class="flex-1">
                <div class="font-semibold">{{ $item->name }}</div>
                <div class="text-sm text-slate-500">{{ $item->category?->name ?? 'Tanpa kategori' }} · {{ $item->formattedPrice() }}</div>
                <div class="text-xs mt-1 {{ $item->is_available ? 'text-green-600' : 'text-red-600' }}">{{ $item->is_available ? 'Tersedia' : 'Habis' }}</div>
                @if($item->track_stock)
                    <div class="text-xs mt-0.5 {{ $item->isLowStock() ? 'text-amber-600 font-medium' : 'text-slate-500' }}">
                        Stok: {{ $item->stock_qty }} pcs
                        @if($item->isLowStock()) · menipis @endif
                    </div>
                @endif
                <div class="flex gap-3 mt-2">
                    <a href="{{ route('admin.menu-items.edit', $item) }}" class="text-xs text-blue-600">Varian</a>
                    <form method="POST" action="{{ route('admin.menu-items.destroy', $item) }}" onsubmit="return confirm('Hapus menu?')">
                    @csrf @method('DELETE')
                    <button class="text-xs text-red-600">Hapus</button>
                </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
