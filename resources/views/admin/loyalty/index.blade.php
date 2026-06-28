@extends('layouts.app')

@section('title', 'Loyalty')

@section('content')
<h1 class="text-2xl font-bold mb-6">Program Loyalty</h1>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 bg-white rounded-xl border p-5">
        <h2 class="font-semibold mb-4">Pengaturan cabang</h2>
        <form method="POST" action="{{ route('admin.loyalty.settings') }}" class="space-y-3 text-sm">
            @csrf @method('PUT')
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="is_enabled" value="1" @checked($settings->is_enabled)> Program aktif
            </label>
            <div>
                <label class="block text-slate-600 mb-1">Earn: setiap Rp</label>
                <input type="number" name="earn_amount_basis" value="{{ old('earn_amount_basis', $settings->earn_amount_basis) }}" min="1" class="w-full border rounded-lg px-3 py-2" required>
            </div>
            <div>
                <label class="block text-slate-600 mb-1">→ dapat poin</label>
                <input type="number" name="earn_points" value="{{ old('earn_points', $settings->earn_points) }}" min="1" class="w-full border rounded-lg px-3 py-2" required>
            </div>
            <div>
                <label class="block text-slate-600 mb-1">1 poin = Rp</label>
                <input type="number" name="redeem_rp_per_point" value="{{ old('redeem_rp_per_point', $settings->redeem_rp_per_point) }}" min="1" class="w-full border rounded-lg px-3 py-2" required>
            </div>
            <div>
                <label class="block text-slate-600 mb-1">Min. redeem (poin)</label>
                <input type="number" name="min_redeem_points" value="{{ old('min_redeem_points', $settings->min_redeem_points) }}" min="1" class="w-full border rounded-lg px-3 py-2" required>
            </div>
            <div>
                <label class="block text-slate-600 mb-1">Maks. redeem (% dari tagihan)</label>
                <input type="number" name="max_redeem_percent" value="{{ old('max_redeem_percent', $settings->max_redeem_percent) }}" min="1" max="100" class="w-full border rounded-lg px-3 py-2" required>
            </div>
            <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-lg">Simpan pengaturan</button>
        </form>
        <p class="text-xs text-slate-500 mt-4">Member dikenali dari nomor telepon order. Poin earn saat pembayaran lunas (per transaksi bayar).</p>
    </div>

    <div class="lg:col-span-2 bg-white rounded-xl border overflow-hidden">
        <div class="p-4 border-b flex flex-wrap gap-3 items-center justify-between">
            <h2 class="font-semibold">Member</h2>
            <form method="GET" class="flex gap-2">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama / telepon" class="border rounded-lg px-3 py-1.5 text-sm">
                <button type="submit" class="text-sm bg-slate-100 px-3 py-1.5 rounded-lg">Cari</button>
            </form>
        </div>
        @if($members->isEmpty())
            <p class="p-4 text-sm text-slate-500">Belum ada member. Member otomatis terdaftar saat pertama kali dapat poin.</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-2">Nama / Telepon</th>
                        <th class="px-4 py-2 text-right">Poin</th>
                        <th class="px-4 py-2">Sesuaikan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $member)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $member->displayName() }}</div>
                                <div class="text-xs text-slate-500">{{ $member->phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-semibold">{{ number_format($member->points) }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.loyalty.members.adjust', $member) }}" class="flex flex-wrap gap-2 items-center">
                                    @csrf
                                    <input type="number" name="points" value="{{ $member->points }}" min="0" class="w-24 border rounded px-2 py-1 text-sm">
                                    <input type="text" name="notes" placeholder="Catatan" class="border rounded px-2 py-1 text-sm flex-1 min-w-[5rem]">
                                    <button type="submit" class="text-xs bg-slate-900 text-white px-3 py-1.5 rounded-lg">Simpan</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
