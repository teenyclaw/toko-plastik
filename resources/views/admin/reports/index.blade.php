@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Laporan</h1>
            <p class="text-sm text-slate-600 mt-1">{{ $report['title'] }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
               class="px-3 py-2 border rounded-lg text-sm bg-white hover:bg-slate-50">Export CSV</a>
            <button type="button" onclick="window.print()" class="px-3 py-2 border rounded-lg text-sm bg-white hover:bg-slate-50 no-print">Cetak</button>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-4 no-print">
        @foreach ($tabs as $id => $label)
            <a href="{{ route('reports.index', ['type' => $id, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}"
               class="px-3 py-1.5 rounded-lg text-sm {{ $type === $id ? 'bg-blue-700 text-white' : 'bg-white border hover:bg-slate-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($type !== 'low-stock')
        <form method="GET" class="mb-4 flex flex-wrap gap-2 items-end no-print">
            <input type="hidden" name="type" value="{{ $type }}">
            <div>
                <label class="text-xs text-slate-600">Dari</label>
                <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="border rounded-lg px-3 py-2 text-sm block">
            </div>
            <div>
                <label class="text-xs text-slate-600">Sampai</label>
                <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="border rounded-lg px-3 py-2 text-sm block">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm">Tampilkan</button>
        </form>
    @endif

    @if ($type === 'profit-loss')
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5 mb-6">
            <div class="bg-white rounded-xl border p-4">
                <p class="text-xs text-slate-500">Pendapatan</p>
                <p class="text-lg font-bold text-green-700">{{ format_rupiah($report['summary']['revenue']) }}</p>
            </div>
            <div class="bg-white rounded-xl border p-4">
                <p class="text-xs text-slate-500">HPP</p>
                <p class="text-lg font-bold">{{ format_rupiah($report['summary']['cogs']) }}</p>
            </div>
            <div class="bg-white rounded-xl border p-4">
                <p class="text-xs text-slate-500">Laba Kotor</p>
                <p class="text-lg font-bold">{{ format_rupiah($report['summary']['gross_profit']) }}</p>
            </div>
            <div class="bg-white rounded-xl border p-4">
                <p class="text-xs text-slate-500">Beban</p>
                <p class="text-lg font-bold text-red-700">{{ format_rupiah($report['summary']['expenses']) }}</p>
            </div>
            <div class="bg-white rounded-xl border p-4">
                <p class="text-xs text-slate-500">Laba Bersih</p>
                <p class="text-lg font-bold {{ $report['summary']['net_profit'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                    {{ format_rupiah($report['summary']['net_profit']) }}
                </p>
            </div>
        </div>
    @elseif (isset($report['summary']['total']))
        <div class="mb-4 text-sm bg-white border rounded-xl p-4">
            <strong>{{ $report['summary']['count'] }}</strong> transaksi —
            Total: <strong>{{ format_rupiah($report['summary']['total']) }}</strong>
            @if ($type !== 'low-stock')
                <span class="text-slate-500">({{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }})</span>
            @endif
        </div>
    @elseif ($type === 'low-stock')
        <div class="mb-4 text-sm bg-amber-50 border border-amber-200 rounded-xl p-4">
            <strong>{{ $report['summary']['count'] }}</strong> produk stok menipis
        </div>
    @endif

    <div class="bg-white rounded-xl border overflow-x-auto print:border-0">
        @if ($type === 'sales')
            <table class="w-full text-sm min-w-[700px]">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Invoice</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Pelanggan</th>
                        <th class="px-4 py-3">Kasir</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $row['invoice'] }}</td>
                            <td class="px-4 py-3">{{ $row['date']->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">{{ $row['party'] }}</td>
                            <td class="px-4 py-3">{{ $row['cashier'] }}</td>
                            <td class="px-4 py-3">{{ $row['method'] }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ format_rupiah($row['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @elseif ($type === 'purchases')
            <table class="w-full text-sm min-w-[600px]">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">PO</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $row['invoice'] }}</td>
                            <td class="px-4 py-3">{{ $row['date']->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">{{ $row['party'] }}</td>
                            <td class="px-4 py-3">{{ $row['method'] }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ format_rupiah($row['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @elseif ($type === 'best-sellers')
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3 text-right">Qty</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($report['rows'] as $i => $row)
                        <tr>
                            <td class="px-4 py-3 text-slate-500">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 font-medium">{{ $row['name'] }}</td>
                            <td class="px-4 py-3 text-right">{{ format_qty($row['qty']) }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ format_rupiah($row['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Tidak ada penjualan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @elseif ($type === 'low-stock')
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3 text-right">Stok</th>
                        <th class="px-4 py-3 text-right">Min</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($report['rows'] as $row)
                        <tr class="bg-amber-50">
                            <td class="px-4 py-3 font-mono text-xs">{{ $row['code'] }}</td>
                            <td class="px-4 py-3">{{ $row['name'] }}</td>
                            <td class="px-4 py-3">{{ $row['category'] }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ format_qty($row['stock']) }} {{ $row['unit'] }}</td>
                            <td class="px-4 py-3 text-right">{{ format_qty($row['min_stock']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Semua stok aman.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @elseif ($type === 'profit-loss')
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Beban</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row['title'] }}</td>
                            <td class="px-4 py-3">{{ $row['category'] }}</td>
                            <td class="px-4 py-3">{{ $row['date']->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $row['user'] }}</td>
                            <td class="px-4 py-3 text-right">{{ format_rupiah($row['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Tidak ada beban operasional.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </div>

    @push('head')
    <style>
        @media print {
            aside, .no-print { display: none !important; }
            main { padding: 0 !important; }
        }
    </style>
    @endpush
@endsection
