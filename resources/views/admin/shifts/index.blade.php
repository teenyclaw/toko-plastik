@extends('layouts.app')

@section('title', 'Riwayat Shift')

@section('content')
<h1 class="text-2xl font-bold mb-6">Riwayat Shift Kasir</h1>

<form method="GET" class="mb-4 flex gap-2">
    <select name="status" class="border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
        <option value="">Semua</option>
        <option value="open" @selected(request('status') === 'open')>Aktif</option>
        <option value="closed" @selected(request('status') === 'closed')>Selesai</option>
    </select>
</form>

<div class="bg-white rounded-xl border overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="p-3">Kasir</th>
                <th class="p-3">Buka</th>
                <th class="p-3">Tutup</th>
                <th class="p-3">Modal</th>
                <th class="p-3">Selisih</th>
                <th class="p-3">Status</th>
                <th class="p-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($shifts as $shift)
                <tr class="border-t">
                    <td class="p-3">{{ $shift->user->name }}</td>
                    <td class="p-3">{{ $shift->opened_at->format('d/m H:i') }}</td>
                    <td class="p-3">{{ $shift->closed_at?->format('d/m H:i') ?? '—' }}</td>
                    <td class="p-3">{{ $shift->formattedOpeningFloat() }}</td>
                    <td class="p-3">
                        @if($shift->cash_difference !== null)
                            <span class="{{ $shift->cash_difference >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                Rp {{ number_format($shift->cash_difference, 0, ',', '.') }}
                            </span>
                        @else — @endif
                    </td>
                    <td class="p-3 capitalize">{{ $shift->status }}</td>
                    <td class="p-3"><a href="{{ route('admin.shifts.show', $shift) }}" class="text-blue-600">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-6 text-center text-slate-500">Belum ada shift.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $shifts->links() }}</div>
@endsection
