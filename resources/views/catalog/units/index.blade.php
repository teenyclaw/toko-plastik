@extends('layouts.app')

@section('title', 'Satuan')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Satuan</h1>
            <p class="text-sm text-slate-600 mt-1">Pcs, Pack, Kg, dan lainnya.</p>
        </div>
        <a href="{{ route('units.create') }}" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm hover:bg-blue-800">+ Tambah</a>
    </div>

    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Singkatan</th>
                    <th class="px-4 py-3">Produk</th>
                    <th class="px-4 py-3 w-32"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($units as $unit)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $unit->name }}</td>
                        <td class="px-4 py-3">{{ $unit->abbreviation }}</td>
                        <td class="px-4 py-3">{{ $unit->products_count }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('units.edit', $unit) }}" class="text-blue-700 hover:underline">Edit</a>
                            @if ($unit->products_count === 0)
                                <form action="{{ route('units.destroy', $unit) }}" method="POST" class="inline" onsubmit="return confirm('Hapus satuan?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada satuan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
