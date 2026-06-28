@extends('layouts.app')

@section('title', 'Shift Kasir')

@section('content')
<div class="max-w-lg">
    <h1 class="text-2xl font-bold mb-6">Shift Kasir</h1>

    @if($shift && $shift->isOpen())
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-4">
            <div class="font-semibold text-emerald-800">Shift aktif</div>
            <div class="text-sm text-emerald-700 mt-1">
                Dibuka {{ $shift->opened_at->format('d/m/Y H:i') }} · Modal awal {{ $shift->formattedOpeningFloat() }}
            </div>
        </div>

        @if($summary)
            <div class="bg-white rounded-xl border p-4 mb-4 grid grid-cols-2 gap-3 text-sm">
                <div><span class="text-slate-500">Pembayaran</span><div class="font-bold">{{ $summary['payment_count'] }}</div></div>
                <div><span class="text-slate-500">Total omzet</span><div class="font-bold">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</div></div>
                <div><span class="text-slate-500">Tunai</span><div class="font-bold">Rp {{ number_format($summary['cash_total'], 0, ',', '.') }}</div></div>
                <div><span class="text-slate-500">Transfer</span><div class="font-bold">Rp {{ number_format($summary['transfer_total'], 0, ',', '.') }}</div></div>
                <div><span class="text-slate-500">QRIS</span><div class="font-bold">Rp {{ number_format($summary['qris_total'], 0, ',', '.') }}</div></div>
                <div><span class="text-slate-500">Estimasi laci</span><div class="font-bold text-emerald-700">Rp {{ number_format($summary['expected_cash_in_drawer'], 0, ',', '.') }}</div></div>
            </div>
        @endif

        <form method="POST" action="{{ route('pos.shift.close', $shift) }}" class="bg-white rounded-xl border p-4 space-y-3">
            @csrf
            <h2 class="font-semibold">Tutup Shift</h2>
            <div>
                <label class="text-sm">Uang fisik di laci (Rp) *</label>
                <input type="number" name="closing_cash" required min="0" value="{{ $summary['expected_cash_in_drawer'] ?? 0 }}" class="w-full border rounded-lg px-3 py-2 mt-1">
            </div>
            <div>
                <label class="text-sm">Catatan</label>
                <textarea name="notes" rows="2" class="w-full border rounded-lg px-3 py-2 mt-1" placeholder="Opsional"></textarea>
            </div>
            <button type="submit" class="w-full bg-slate-900 text-white py-2.5 rounded-lg font-medium">Tutup Shift</button>
        </form>
    @else
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4 text-sm text-amber-900">
            Tidak ada shift aktif. Buka shift sebelum menerima pembayaran.
        </div>

        <form method="POST" action="{{ route('pos.shift.open') }}" class="bg-white rounded-xl border p-4 space-y-3">
            @csrf
            <h2 class="font-semibold">Buka Shift Baru</h2>
            <div>
                <label class="text-sm">Modal awal kas (Rp)</label>
                <input type="number" name="opening_float" min="0" value="0" class="w-full border rounded-lg px-3 py-2 mt-1">
                <p class="text-xs text-slate-500 mt-1">Uang kembalian di laci saat mulai shift.</p>
            </div>
            <button type="submit" class="w-full bg-emerald-600 text-white py-2.5 rounded-lg font-medium">Buka Shift</button>
        </form>
    @endif
</div>
@endsection
