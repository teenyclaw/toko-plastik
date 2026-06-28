@extends('layouts.app')

@section('title', 'Pembelian')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Pembelian</h1>
            <p class="text-sm text-slate-600 mt-1">Riwayat penerimaan barang dari supplier.</p>
        </div>
        <a href="{{ route('purchases.create') }}" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm hover:bg-blue-800">+ Pembelian Baru</a>
    </div>

    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">No. PO</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Supplier</th>
                    <th class="px-4 py-3">Item</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Bayar</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($purchases as $purchase)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $purchase->invoice_number }}</td>
                        <td class="px-4 py-3">{{ $purchase->date->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $purchase->supplier->name }}</td>
                        <td class="px-4 py-3">{{ $purchase->details_count }} item</td>
                        <td class="px-4 py-3 font-medium">{{ format_rupiah($purchase->total) }}</td>
                        <td class="px-4 py-3">
                            {{ $purchase->payment_method->label() }}
                            @if ((float) $purchase->paid < (float) $purchase->total)
                                <span class="text-xs text-red-600 block">Tempo {{ format_rupiah($purchase->total - $purchase->paid) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('purchases.show', $purchase) }}" class="text-blue-700 hover:underline">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada pembelian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $purchases->links() }}</div>
@endsection
