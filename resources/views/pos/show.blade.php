@extends('layouts.app')

@section('title', $order->order_number)

@section('content')
<div class="max-w-2xl">
    <a href="{{ $order->dining_table_id ? route('pos.tables.index') : route('pos.queue') }}" class="text-sm text-blue-600 mb-4 inline-block">← Kembali</a>

    <div class="bg-white rounded-xl border p-5 mb-4">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-xl font-bold font-mono">{{ $order->order_number }}</h1>
                <span class="inline-block mt-1 px-2 py-0.5 rounded text-xs {{ $order->statusEnum()->badgeClass() }}">{{ $order->statusEnum()->label() }}</span>
                <span class="inline-block mt-1 ml-1 px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-700">{{ $order->sourceEnum()->label() }}</span>
            </div>
            <div class="text-sm text-slate-500 text-right">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
        </div>

        @if($order->isKitchenActive())
            <div class="mb-4 p-3 rounded-lg bg-orange-50 border border-orange-200 text-sm text-orange-900">
                Status dapur: <strong>{{ $order->statusEnum()->label() }}</strong>
                @if($order->kitchen_started_at)
                    · mulai masak {{ $order->kitchen_started_at->format('H:i') }}
                @endif
                @if($order->kitchen_ready_at)
                    · siap {{ $order->kitchen_ready_at->format('H:i') }}
                @endif
            </div>
        @endif

        <div class="text-sm space-y-1 mb-4">
            @if($order->diningTable)
                <div><strong>Meja:</strong> {{ $order->diningTable->name }}</div>
            @endif
            <div><strong>Pelanggan:</strong> {{ $order->customer_name }}</div>
            @if($order->customer_phone !== '-')
                <div><strong>Telepon:</strong> {{ $order->customer_phone }}</div>
            @endif
            @if($order->notes)<div><strong>Catatan:</strong> {{ $order->notes }}</div>@endif
        </div>

        <table class="w-full text-sm border-t">
            <thead><tr class="text-left text-slate-500"><th class="py-2">Item</th><th class="py-2 text-center">Qty</th><th class="py-2 text-right">Subtotal</th></tr></thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr class="border-t border-slate-100 {{ $item->is_paid ? 'opacity-50' : '' }}">
                        <td class="py-2">
                            {{ $item->item_name }}
                            @if($item->modifierSummary())<br><span class="text-xs text-purple-600">{{ $item->modifierSummary() }}</span>@endif
                            @if($item->note)<br><span class="text-xs text-slate-400">{{ $item->note }}</span>@endif
                            @if($item->is_paid)<br><span class="text-xs text-green-600">Lunas</span>@endif
                        </td>
                        <td class="py-2 text-center">{{ $item->qty }}</td>
                        <td class="py-2 text-right">{{ $item->formattedSubtotal() }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t font-bold"><td colspan="2" class="py-3">Total</td><td class="py-3 text-right">{{ $order->formattedTotal() }}</td></tr>
                @if($order->hasUnpaidItems())
                    <tr class="text-orange-700"><td colspan="2" class="pb-3">Sisa tagihan</td><td class="pb-3 text-right">{{ $order->formattedRemainingBalance() }}</td></tr>
                @endif
            </tfoot>
        </table>
    </div>

    <div class="flex flex-wrap gap-2">
        @if($order->dining_table_id && $order->isActiveBill())
            <a href="{{ route('pos.tables.order', $order) }}" class="bg-slate-900 text-white px-4 py-2 rounded-lg">Tambah Item (Waiter)</a>
        @endif
        @if(in_array($order->status, ['pending', 'cooking', 'ready']))
            <form method="POST" action="{{ route('pos.orders.confirm', $order) }}">@csrf
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg">
                    @if($order->status === 'ready') Sudah Disajikan @else Tandai Disajikan @endif
                </button>
            </form>
            <form method="POST" action="{{ route('pos.orders.cancel', $order) }}" onsubmit="return confirm('Batalkan pesanan?')">@csrf<button class="border border-red-300 text-red-600 px-4 py-2 rounded-lg">Batalkan</button></form>
        @endif
        @if(in_array($order->status, ['pending', 'confirmed', 'open', 'ready', 'cooking']) || $order->hasUnpaidItems())
            <a href="{{ route('pos.payment', $order) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg">
                @if($order->hasUnpaidItems() && $order->status === 'paid') Bayar Sisa @else Bayar / Close Bill @endif
            </a>
        @endif
        @if($order->status === 'confirmed')
            <form method="POST" action="{{ route('pos.orders.complete', $order) }}">@csrf<button class="border px-4 py-2 rounded-lg">Tandai Selesai</button></form>
        @endif
        @if($order->status === 'paid')
            @php $lastPayment = $order->payments->where('status', 'paid')->sortByDesc('paid_at')->first(); @endphp
            <a href="{{ $lastPayment ? route('pos.receipt', [$order, $lastPayment]) : route('pos.receipt', $order) }}?auto=1" target="_blank" class="border px-4 py-2 rounded-lg">🖨 Cetak Struk</a>
        @endif
        <a href="{{ route('kitchen.display') }}" target="_blank" class="border px-4 py-2 rounded-lg text-sm">Layar Dapur ↗</a>
    </div>
</div>
@endsection
