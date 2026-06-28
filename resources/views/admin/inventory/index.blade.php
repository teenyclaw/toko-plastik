@extends('layouts.app')

@section('title', 'Stok')

@section('content')
<h1 class="text-2xl font-bold mb-6">Inventori Stok</h1>

@if($lowStock->isNotEmpty())
    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
        <h2 class="font-semibold text-amber-900 mb-2">Stok menipis ({{ $lowStock->count() }})</h2>
        <ul class="text-sm text-amber-800 space-y-1">
            @foreach($lowStock as $item)
                <li>{{ $item->name }} — <strong>{{ $item->stock_qty }}</strong> pcs (batas: {{ $item->low_stock_threshold }})</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-xl border overflow-hidden">
        <div class="p-4 border-b font-semibold">Menu dengan pelacakan stok</div>
        @if($items->isEmpty())
            <p class="p-4 text-sm text-slate-500">Belum ada menu dengan pelacakan stok. Aktifkan di halaman Menu.</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-2">Menu</th>
                        <th class="px-4 py-2 text-right">Stok</th>
                        <th class="px-4 py-2 text-right">Batas</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Sesuaikan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $item->name }}</div>
                                <div class="text-xs text-slate-500">{{ $item->category?->name ?? 'Tanpa kategori' }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-mono {{ $item->isLowStock() ? 'text-amber-600 font-semibold' : '' }}">{{ $item->stock_qty }}</td>
                            <td class="px-4 py-3 text-right text-slate-500">{{ $item->low_stock_threshold }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs {{ $item->is_available ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $item->is_available ? 'Tersedia' : 'Habis' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.inventory.adjust', $item) }}" class="flex flex-wrap gap-2 items-center">
                                    @csrf
                                    <input type="number" name="stock_qty" value="{{ $item->stock_qty }}" min="0" class="w-20 border rounded px-2 py-1 text-sm">
                                    <input type="text" name="notes" placeholder="Catatan" class="border rounded px-2 py-1 text-sm flex-1 min-w-[6rem]">
                                    <button type="submit" class="text-xs bg-slate-900 text-white px-3 py-1.5 rounded-lg">Simpan</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="bg-white rounded-xl border overflow-hidden">
        <div class="p-4 border-b font-semibold">Riwayat pergerakan</div>
        @if($recentMovements->isEmpty())
            <p class="p-4 text-sm text-slate-500">Belum ada pergerakan stok.</p>
        @else
            <ul class="divide-y divide-slate-100 text-sm max-h-[32rem] overflow-y-auto">
                @foreach($recentMovements as $move)
                    <li class="px-4 py-3">
                        <div class="font-medium">{{ $move->menuItem->name }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            {{ ucfirst($move->type) }}
                            · {{ $move->quantity > 0 ? '+' : '' }}{{ $move->quantity }}
                            → {{ $move->stock_after }} pcs
                            · {{ $move->created_at->format('d/m H:i') }}
                        </div>
                        @if($move->notes)
                            <div class="text-xs text-slate-400 mt-0.5">{{ $move->notes }}</div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
