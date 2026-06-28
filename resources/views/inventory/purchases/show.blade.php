@extends('layouts.app')

@section('title', 'Detail Pembelian')

@section('content')
    <div class="max-w-3xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">{{ $purchase->invoice_number }}</h1>
                <p class="text-sm text-slate-600 mt-1">{{ $purchase->date->format('d/m/Y H:i') }} · {{ $purchase->user->name }}</p>
            </div>
            <a href="{{ route('purchases.index') }}" class="text-sm text-blue-700 hover:underline">← Kembali</a>
        </div>

        <div class="bg-white rounded-xl border p-5 mb-4 text-sm space-y-2">
            <div class="flex justify-between"><span class="text-slate-500">Supplier</span><span class="font-medium">{{ $purchase->supplier->name }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Metode bayar</span><span>{{ $purchase->payment_method->label() }}</span></div>
            @if ($purchase->notes)
                <div class="flex justify-between"><span class="text-slate-500">Catatan</span><span>{{ $purchase->notes }}</span></div>
            @endif
        </div>

        <div class="bg-white rounded-xl border overflow-hidden mb-4">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">Qty</th>
                        <th class="px-4 py-3">Harga</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($purchase->details as $detail)
                        <tr>
                            <td class="px-4 py-3">{{ $detail->product->name }}</td>
                            <td class="px-4 py-3">{{ format_qty($detail->quantity) }} {{ $detail->unit->abbreviation }}</td>
                            <td class="px-4 py-3">{{ format_rupiah($detail->unit_price) }}</td>
                            <td class="px-4 py-3 text-right">{{ format_rupiah($detail->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 text-sm">
                    <tr><td colspan="3" class="px-4 py-2 text-right">Subtotal</td><td class="px-4 py-2 text-right">{{ format_rupiah($purchase->subtotal) }}</td></tr>
                    @if ((float) $purchase->discount > 0)
                        <tr><td colspan="3" class="px-4 py-2 text-right">Diskon</td><td class="px-4 py-2 text-right">-{{ format_rupiah($purchase->discount) }}</td></tr>
                    @endif
                    <tr><td colspan="3" class="px-4 py-3 text-right font-bold">Total</td><td class="px-4 py-3 text-right font-bold">{{ format_rupiah($purchase->total) }}</td></tr>
                    <tr><td colspan="3" class="px-4 py-2 text-right">Dibayar</td><td class="px-4 py-2 text-right">{{ format_rupiah($purchase->paid) }}</td></tr>
                    @if ((float) $purchase->paid < (float) $purchase->total)
                        <tr><td colspan="3" class="px-4 py-2 text-right text-red-700">Hutang</td><td class="px-4 py-2 text-right text-red-700">{{ format_rupiah($purchase->total - $purchase->paid) }}</td></tr>
                    @endif
                </tfoot>
            </table>
        </div>

        <p class="text-xs text-green-700 bg-green-50 border border-green-200 rounded-lg p-3">
            Stok produk sudah ditambahkan otomatis dan harga beli diperbarui.
        </p>
    </div>
@endsection
