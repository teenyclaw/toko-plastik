@extends('layouts.app')

@section('title', 'Dashboard — ' . config('app.name'))

@section('content')
    <div class="max-w-5xl">
        <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
        <p class="text-slate-600 mt-1">Selamat datang, {{ auth()->user()->name }} ({{ $roleLabel }}).</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Penjualan Hari Ini</p>
                <p class="text-2xl font-bold mt-1 text-green-700">{{ format_rupiah($todayTotal) }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ $todayCount }} transaksi</p>
            </div>
            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Produk Stok Menipis</p>
                <p class="text-2xl font-bold mt-1 {{ $lowStockCount > 0 ? 'text-amber-600' : 'text-slate-700' }}">{{ $lowStockCount }}</p>
                <p class="text-xs text-slate-500 mt-1">Stok &le; minimum</p>
            </div>
            <div class="rounded-xl border bg-white p-5 shadow-sm sm:col-span-2 lg:col-span-1">
                <p class="text-sm text-slate-500">Status</p>
                <p class="text-xl font-semibold mt-1 text-blue-700">Production ready</p>
                <p class="text-sm text-slate-600 mt-2">Semua modul Fase 0–4 siap deploy. Lihat docs/HOSTING.md.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <h2 class="font-semibold mb-3">Transaksi Terakhir (Hari Ini)</h2>
                @forelse ($recentSales as $sale)
                    <div class="flex justify-between text-sm py-2 border-b last:border-0">
                        <div>
                            <div class="font-medium">{{ $sale->invoice_number }}</div>
                            <div class="text-xs text-slate-500">{{ $sale->date->format('H:i') }} · {{ $sale->user->name }}</div>
                        </div>
                        <div class="font-semibold">{{ format_rupiah($sale->total) }}</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada transaksi hari ini.</p>
                @endforelse
            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <h2 class="font-semibold mb-3">Terlaris Hari Ini</h2>
                @forelse ($topProducts as $row)
                    <div class="flex justify-between text-sm py-2 border-b last:border-0">
                        <span>{{ $row->name }}</span>
                        <span class="text-slate-600">{{ format_qty($row->qty) }} terjual</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada data penjualan.</p>
                @endforelse
            </div>
        </div>

        @if (auth()->user()->hasRole(\App\Enums\UserRole::Owner, \App\Enums\UserRole::Kasir))
            <div class="mt-6">
                <a href="{{ route('pos.index') }}" class="inline-flex px-5 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700">
                    Buka Kasir POS →
                </a>
            </div>
        @endif
    </div>
@endsection
