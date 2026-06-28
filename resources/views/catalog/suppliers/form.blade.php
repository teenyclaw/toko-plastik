@extends('layouts.app')

@section('title', ($supplier->exists ? 'Edit' : 'Tambah') . ' Supplier')

@section('content')
    <h1 class="text-2xl font-bold mb-6">{{ $supplier->exists ? 'Edit' : 'Tambah' }} Supplier</h1>

    <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}"
          class="max-w-lg bg-white rounded-xl border p-6 space-y-4">
        @csrf
        @if ($supplier->exists) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium mb-1">Nama</label>
            <input type="text" name="name" value="{{ old('name', $supplier->name) }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Kontak (WA/Telp)</label>
            <input type="text" name="contact" value="{{ old('contact', $supplier->contact) }}" class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Alamat</label>
            <textarea name="address" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('address', $supplier->address) }}</textarea>
        </div>
        @if ($supplier->exists && (float) $supplier->balance > 0)
            <div class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
                Hutang ke supplier: <strong>{{ format_rupiah($supplier->balance) }}</strong>
                <span class="block text-xs mt-1">Kelola pelunasan di modul Keuangan (Fase 3).</span>
            </div>
        @endif
        <div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active ?? true))>
                Supplier aktif
            </label>
        </div>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm">Simpan</button>
            <a href="{{ route('suppliers.index') }}" class="px-4 py-2 border rounded-lg text-sm">Batal</a>
        </div>
    </form>
@endsection
