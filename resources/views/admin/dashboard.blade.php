@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1 class="text-2xl font-bold mb-6">Dashboard</h1>

@if(!empty($lowStock) && $lowStock->isNotEmpty())
    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="font-semibold text-amber-900">{{ $lowStock->count() }} menu stok menipis</div>
            <div class="text-sm text-amber-800 mt-1">{{ $lowStock->pluck('name')->take(3)->join(', ') }}@if($lowStock->count() > 3) … @endif</div>
        </div>
        <a href="{{ route('admin.inventory.index') }}" class="text-sm font-medium text-amber-900 underline">Kelola stok →</a>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-xl border p-5">
        <div class="text-sm text-slate-500">Penjualan Hari Ini</div>
        <div class="text-2xl font-bold mt-1">Rp {{ number_format($stats['revenue'], 0, ',', '.') }}</div>
        <a href="{{ route('admin.reports.index') }}" class="text-xs text-emerald-700 mt-2 inline-block hover:underline">Lihat laporan periode →</a>
    </div>
    <div class="bg-white rounded-xl border p-5">
        <div class="text-sm text-slate-500">Order Lunas Hari Ini</div>
        <div class="text-2xl font-bold mt-1">{{ $stats['orders'] }}</div>
    </div>
    <div class="bg-white rounded-xl border p-5">
        <div class="text-sm text-slate-500">Outlet</div>
        <div class="text-lg font-semibold mt-1">{{ $outlet?->name ?? '-' }}</div>
    </div>
</div>

<div class="bg-white rounded-xl border p-5">
    <h2 class="font-semibold mb-4">Menu Terlaris Hari Ini</h2>
    @if(empty($topItems))
        <p class="text-slate-500 text-sm">Belum ada data penjualan hari ini.</p>
    @else
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-500 border-b"><th class="pb-2">Menu</th><th class="pb-2 text-right">Qty</th></tr></thead>
            <tbody>
                @foreach($topItems as $row)
                    <tr class="border-b border-slate-100"><td class="py-2">{{ $row['item_name'] }}</td><td class="py-2 text-right">{{ $row['total_qty'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
