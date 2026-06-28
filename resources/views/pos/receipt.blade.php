<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $sale->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
        }
        body { font-family: monospace, ui-monospace, monospace; }
    </style>
</head>
<body class="bg-slate-100 p-4">
    <div class="max-w-sm mx-auto bg-white p-4 shadow print:shadow-none text-sm">
        <div class="text-center mb-3">
            <div class="font-bold text-base">{{ $storeName }}</div>
            @if ($storeAddress)
                <div class="text-xs mt-1">{{ $storeAddress }}</div>
            @endif
        </div>

        <div class="border-t border-dashed border-slate-400 py-2 text-xs space-y-1">
            <div>No: {{ $sale->invoice_number }}</div>
            <div>{{ $sale->date->format('d/m/Y H:i') }}</div>
            <div>Kasir: {{ $sale->user->name }}</div>
            @if ($sale->customer)
                <div>Pelanggan: {{ $sale->customer->name }}</div>
            @endif
        </div>

        <table class="w-full text-xs my-2">
            @foreach ($sale->details as $detail)
                <tr>
                    <td colspan="3" class="pt-2">{{ $detail->product->name }}</td>
                </tr>
                <tr class="text-slate-600">
                    <td>{{ format_qty($detail->quantity) }} {{ $detail->unit->abbreviation }} x {{ format_rupiah($detail->unit_price) }}</td>
                    <td></td>
                    <td class="text-right">{{ format_rupiah($detail->total) }}</td>
                </tr>
            @endforeach
        </table>

        <div class="border-t border-dashed border-slate-400 pt-2 space-y-1 text-xs">
            <div class="flex justify-between"><span>Subtotal</span><span>{{ format_rupiah($sale->subtotal) }}</span></div>
            @if ((float) $sale->discount > 0)
                <div class="flex justify-between"><span>Diskon</span><span>-{{ format_rupiah($sale->discount) }}</span></div>
            @endif
            <div class="flex justify-between font-bold text-sm"><span>TOTAL</span><span>{{ format_rupiah($sale->total) }}</span></div>
            <div class="flex justify-between"><span>Bayar ({{ $sale->payment_method->label() }})</span><span>{{ format_rupiah($sale->paid) }}</span></div>
            @if ((float) $sale->change_amount > 0)
                <div class="flex justify-between"><span>Kembalian</span><span>{{ format_rupiah($sale->change_amount) }}</span></div>
            @endif
        </div>

        <div class="text-center text-xs mt-4 text-slate-600">{{ $receiptFooter }}</div>

        <div class="no-print mt-6 flex gap-2 justify-center">
            <button onclick="window.print()" class="px-4 py-2 bg-blue-700 text-white rounded text-sm">Cetak</button>
            <a href="{{ route('pos.index') }}" class="px-4 py-2 border rounded text-sm">Transaksi Baru</a>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 border rounded text-sm">Dashboard</a>
        </div>
    </div>
</body>
</html>
