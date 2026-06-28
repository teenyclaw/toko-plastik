@extends('layouts.app')

@section('title', 'Copy Menu')

@section('content')
<h1 class="text-2xl font-bold mb-2">Copy Menu Antar Cabang</h1>
<p class="text-sm text-slate-500 mb-6">Salin kategori, menu, foto, dan varian dari satu cabang ke cabang lain.</p>

<form method="POST" action="{{ route('admin.menu-copy.store') }}" class="max-w-xl bg-white rounded-xl border p-5 space-y-4" onsubmit="return confirm('Salin menu ke cabang tujuan?')">
    @csrf

    <div>
        <label class="block text-sm font-medium mb-1">Cabang sumber *</label>
        <select name="from_outlet_id" required class="w-full border rounded-lg px-3 py-2">
            @foreach($outlets as $outlet)
                <option value="{{ $outlet->id }}" @selected(old('from_outlet_id', $defaultFromId ?? null) == $outlet->id)>{{ $outlet->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Cabang tujuan *</label>
        <select name="to_outlet_id" required class="w-full border rounded-lg px-3 py-2">
            <option value="">— Pilih cabang —</option>
            @foreach($outlets as $outlet)
                <option value="{{ $outlet->id }}" @selected(old('to_outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
            @endforeach
        </select>
        @error('to_outlet_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="space-y-2 text-sm border rounded-lg p-3 bg-slate-50">
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="include_modifiers" value="1" @checked(old('include_modifiers', true))> Sertakan varian (grup & opsi)
        </label>
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="copy_stock" value="1" @checked(old('copy_stock'))> Salin pengaturan stok
        </label>
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="overwrite" value="1" @checked(old('overwrite'))> Timpa menu dengan nama sama di cabang tujuan
        </label>
    </div>

    <p class="text-xs text-slate-500">
        Tanpa <strong>timpa</strong>, menu/kategori dengan nama yang sama di cabang tujuan akan dilewati.
        Foto menu disalin sebagai file terpisah.
    </p>

    <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-lg">Salin Menu</button>
</form>
@endsection
