@extends('layouts.app')

@section('title', 'Riwayat Order')

@section('content')
<h1 class="text-2xl font-bold mb-6">Riwayat Pesanan</h1>

<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <input type="search" name="search" value="{{ $search }}" placeholder="Cari nomor/nama..." class="border rounded-lg px-3 py-2 text-sm">
    <select name="status" class="border rounded-lg px-3 py-2 text-sm">
        @foreach(['all'=>'Semua','pending'=>'Menunggu','confirmed'=>'Dikonfirmasi','paid'=>'Lunas','completed'=>'Selesai','cancelled'=>'Batal'] as $val=>$label)
            <option value="{{ $val }}" @selected($status===$val)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm">Filter</button>
</form>

<div class="bg-white rounded-xl border overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left"><tr>
            <th class="p-3">No. Order</th><th class="p-3">Pelanggan</th><th class="p-3">Total</th><th class="p-3">Status</th><th class="p-3">Waktu</th>
        </tr></thead>
        <tbody>
            @forelse($orders as $order)
                <tr class="border-t">
                    <td class="p-3 font-mono"><a href="{{ route('pos.orders.show', $order) }}" class="text-blue-600">{{ $order->order_number }}</a></td>
                    <td class="p-3">{{ $order->customer_name }}<br><span class="text-slate-500">{{ $order->customer_phone }}</span></td>
                    <td class="p-3">{{ $order->formattedTotal() }}</td>
                    <td class="p-3"><span class="px-2 py-0.5 rounded text-xs {{ $order->statusEnum()->badgeClass() }}">{{ $order->statusEnum()->label() }}</span></td>
                    <td class="p-3 text-slate-500">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-slate-500">Tidak ada pesanan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
