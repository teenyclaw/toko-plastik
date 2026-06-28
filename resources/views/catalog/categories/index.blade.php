@extends('layouts.app')

@section('title', 'Kategori — ' . config('app.name'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Kategori</h1>
            <p class="text-sm text-slate-600 mt-1">Kelompok produk plastik & bahan kue.</p>
        </div>
        <a href="{{ route('categories.create') }}" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm hover:bg-blue-800">+ Tambah</a>
    </div>

    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Tipe</th>
                    <th class="px-4 py-3">Produk</th>
                    <th class="px-4 py-3 w-32"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                        <td class="px-4 py-3">{{ $category->type->label() }}</td>
                        <td class="px-4 py-3">{{ $category->products_count }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('categories.edit', $category) }}" class="text-blue-700 hover:underline">Edit</a>
                            @if ($category->products_count === 0)
                                <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kategori?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada kategori.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
