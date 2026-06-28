@extends('layouts.app')

@section('title', 'Detail Shift')

@section('content')
<a href="{{ route('admin.shifts.index') }}" class="text-sm text-blue-600 mb-4 inline-block">← Riwayat shift</a>
<h1 class="text-2xl font-bold mb-1">Shift #{{ $shift->id }}</h1>
<p class="text-sm text-slate-500 mb-6">{{ $shift->user->name }} · {{ $shift->opened_at->format('d/m/Y H:i') }}</p>

<div class="grid md:grid-cols-2 gap-4 mb-6 text-sm">
    <div class="bg-white rounded-xl border p-4 space-y-2">
        <div class="flex justify-between"><span>Modal awal</span><strong>{{ $shift->formattedOpeningFloat() }}</strong></div>
        <div class="flex justify-between"><span>Estimasi laci</span><strong>Rp {{ number_format($summary['expected_cash_in_drawer'], 0, ',', '.') }}</strong></div>
        @if($shift->closing_cash !== null)
            <div class="flex justify-between"><span>Uang di laci</span><strong>Rp {{ number_format($shift->closing_cash, 0, ',', '.') }}</strong></div>
            <div class="flex justify-between"><span>Selisih</span><strong class="{{ ($shift->cash_difference ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">Rp {{ number_format($shift->cash_difference ?? 0, 0, ',', '.') }}</strong></div>
        @endif
    </div>
    <div class="bg-white rounded-xl border p-4 space-y-2">
        <div class="flex justify-between"><span>Omzet shift</span><strong>Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</strong></div>
        <div class="flex justify-between"><span>Tunai</span><span>Rp {{ number_format($summary['cash_total'], 0, ',', '.') }}</span></div>
        <div class="flex justify-between"><span>Transfer</span><span>Rp {{ number_format($summary['transfer_total'], 0, ',', '.') }}</span></div>
        <div class="flex justify-between"><span>QRIS</span><span>Rp {{ number_format($summary['qris_total'], 0, ',', '.') }}</span></div>
    </div>
</div>

<div class="bg-white rounded-xl border p-4">
    <h2 class="font-semibold mb-3">Pembayaran ({{ $summary['payment_count'] }})</h2>
    @forelse($shift->payments as $payment)
        <div class="flex justify-between py-2 border-t text-sm">
            <span>{{ $payment->order->order_number ?? '—' }} · {{ strtoupper($payment->payment_method) }}</span>
            <span>{{ $payment->formattedAmount() }}</span>
        </div>
    @empty
        <p class="text-slate-500 text-sm">Belum ada pembayaran.</p>
    @endforelse
</div>
@endsection
