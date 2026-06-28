@extends('layouts.app')

@section('title', 'Kategori')

@section('content')
<h1 class="text-2xl font-bold mb-6">Kategori Menu</h1>

<form method="POST" action="{{ route('admin.categories.store') }}" class="bg-white rounded-xl border p-4 mb-6 grid md:grid-cols-4 gap-3 items-end">
    @csrf
    <div class="md:col-span-2">
        <label class="block text-sm mb-1">Nama kategori</label>
        <input type="text" name="name" required class="w-full border rounded-lg px-3 py-2">
    </div>
    <div>
        <label class="block text-sm mb-1">Urutan</label>
        <input type="number" name="sort_order" value="0" min="0" class="w-full border rounded-lg px-3 py-2">
    </div>
    <div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Aktif</label>
        <button type="submit" class="block mt-2 w-full bg-slate-900 text-white py-2 rounded-lg">Tambah</button>
    </div>
</form>

<div class="bg-white rounded-xl border overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left">
            <tr><th class="p-3">Nama</th><th class="p-3">Menu</th><th class="p-3">Urutan</th><th class="p-3">Status</th><th class="p-3">Aksi</th></tr>
        </thead>
        <tbody>
            @forelse($categories as $cat)
                <tr class="border-t">
                    <td class="p-3 font-medium">{{ $cat->name }}</td>
                    <td class="p-3">{{ $cat->menu_items_count }}</td>
                    <td class="p-3">{{ $cat->sort_order }}</td>
                    <td class="p-3">{{ $cat->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td class="p-3">
                        <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" onsubmit="return confirm('Hapus kategori?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-slate-500">Belum ada kategori.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
