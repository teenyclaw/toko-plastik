@extends('layouts.app')

@section('title', 'Supplier')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Supplier</h1>
            <p class="text-sm text-slate-600 mt-1">Pemasok barang toko.</p>
        </div>
        <a href="{{ route('suppliers.create') }}" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm hover:bg-blue-800">+ Tambah</a>
    </div>

    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Kontak</th>
                    <th class="px-4 py-3">Hutang</th>
                    <th class="px-4 py-3">Produk</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 w-32"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($suppliers as $supplier)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $supplier->name }}</div>
                            @if ($supplier->address)
                                <div class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($supplier->address, 40) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $supplier->contact ?? '—' }}</td>
                        <td class="px-4 py-3 {{ (float) $supplier->balance > 0 ? 'text-red-700 font-medium' : '' }}">
                            {{ format_rupiah($supplier->balance) }}
                        </td>
                        <td class="px-4 py-3">{{ $supplier->products_count }}</td>
                        <td class="px-4 py-3">
                            @if ($supplier->is_active)
                                <span class="text-green-700">Aktif</span>
                            @else
                                <span class="text-slate-500">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="text-blue-700 hover:underline">Edit</a>
                            @if ($supplier->products_count === 0 && $supplier->purchases_count === 0)
                                <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="inline" onsubmit="return confirm('Hapus supplier?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada supplier.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
