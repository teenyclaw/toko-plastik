@extends('layouts.app')

@section('title', 'Beban Operasional')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Beban Operasional</h1>
            <p class="text-sm text-slate-600 mt-1">Catat pengeluaran toko di luar pembelian barang.</p>
        </div>
        <a href="{{ route('expenses.create') }}" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm hover:bg-blue-800">+ Tambah Beban</a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2 items-end">
        <div>
            <label class="text-xs text-slate-600">Dari</label>
            <input type="date" name="from" value="{{ $from?->format('Y-m-d') }}" class="border rounded-lg px-3 py-2 text-sm block">
        </div>
        <div>
            <label class="text-xs text-slate-600">Sampai</label>
            <input type="date" name="to" value="{{ $to?->format('Y-m-d') }}" class="border rounded-lg px-3 py-2 text-sm block">
        </div>
        <button type="submit" class="px-4 py-2 border rounded-lg text-sm bg-white">Filter</button>
    </form>

    <div class="mb-4 text-sm text-slate-600">
        Total periode: <strong>{{ format_rupiah($totalAmount) }}</strong>
    </div>

    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Judul</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3">Nominal</th>
                    <th class="px-4 py-3">Dicatat oleh</th>
                    <th class="px-4 py-3 w-24"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($expenses as $expense)
                    <tr>
                        <td class="px-4 py-3">{{ $expense->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $expense->title }}</div>
                            @if ($expense->notes)
                                <div class="text-xs text-slate-500">{{ $expense->notes }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $expense->category ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium">{{ format_rupiah($expense->amount) }}</td>
                        <td class="px-4 py-3">{{ $expense->user->name }}</td>
                        <td class="px-4 py-3 space-x-2">
                            <a href="{{ route('expenses.edit', $expense) }}" class="text-blue-700 hover:underline">Edit</a>
                            <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="inline" onsubmit="return confirm('Hapus beban ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada beban operasional.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $expenses->links() }}</div>
@endsection
