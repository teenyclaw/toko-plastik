@extends('layouts.app')

@section('title', 'Keuangan')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Piutang & Hutang</h1>
        <p class="text-sm text-slate-600 mt-1">Monitor piutang pelanggan, hutang supplier, dan catat pelunasan.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        <div class="bg-white rounded-xl border p-5">
            <p class="text-sm text-slate-500">Total Piutang</p>
            <p class="text-2xl font-bold text-green-700">{{ format_rupiah($totalReceivables) }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $receivables->count() }} pelanggan</p>
        </div>
        <div class="bg-white rounded-xl border p-5">
            <p class="text-sm text-slate-500">Total Hutang Supplier</p>
            <p class="text-2xl font-bold text-red-700">{{ format_rupiah($totalPayables) }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $payables->count() }} supplier</p>
        </div>
        <div class="bg-white rounded-xl border p-5">
            <p class="text-sm text-slate-500">Beban Bulan Ini</p>
            <p class="text-2xl font-bold text-slate-800">{{ format_rupiah($monthExpenses) }}</p>
            <a href="{{ route('expenses.index') }}" class="text-xs text-blue-700 hover:underline mt-1 inline-block">Lihat beban →</a>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl border p-5">
            <h2 class="font-semibold mb-3">Piutang Pelanggan</h2>
            @forelse ($receivables as $customer)
                <div class="flex justify-between py-2 border-b last:border-0 text-sm">
                    <div>
                        <div class="font-medium">{{ $customer->name }}</div>
                        @if ($customer->whatsapp)
                            <div class="text-xs text-slate-500">{{ $customer->whatsapp }}</div>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="font-semibold text-green-700">{{ format_rupiah($customer->balance) }}</div>
                        @if ((float) $customer->credit_limit > 0)
                            <div class="text-xs text-slate-500">Limit {{ format_rupiah($customer->credit_limit) }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Tidak ada piutang.</p>
            @endforelse
        </div>

        <div class="bg-white rounded-xl border p-5">
            <h2 class="font-semibold mb-3">Hutang Supplier</h2>
            @forelse ($payables as $supplier)
                <div class="flex justify-between py-2 border-b last:border-0 text-sm">
                    <div>
                        <div class="font-medium">{{ $supplier->name }}</div>
                        @if ($supplier->contact)
                            <div class="text-xs text-slate-500">{{ $supplier->contact }}</div>
                        @endif
                    </div>
                    <div class="font-semibold text-red-700">{{ format_rupiah($supplier->balance) }}</div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Tidak ada hutang.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl border p-5 mb-6" x-data="{ type: 'customer' }">
        <h2 class="font-semibold mb-4">Catat Pelunasan</h2>
        <form method="POST" action="{{ route('finance.collect') }}" class="flex flex-wrap gap-4 items-end">
            @csrf
            <div>
                <label class="text-xs text-slate-600 block mb-1">Jenis</label>
                <select name="collect_type" x-model="type" class="border rounded-lg px-3 py-2 text-sm">
                    <option value="customer">Piutang pelanggan</option>
                    <option value="supplier">Hutang supplier</option>
                </select>
            </div>
            <div x-show="type === 'customer'">
                <label class="text-xs text-slate-600 block mb-1">Pelanggan</label>
                <select name="customer_id" class="border rounded-lg px-3 py-2 text-sm min-w-[200px]">
                    <option value="">— Pilih —</option>
                    @foreach ($receivables as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ format_rupiah($c->balance) }})</option>
                    @endforeach
                </select>
            </div>
            <div x-show="type === 'supplier'" x-cloak>
                <label class="text-xs text-slate-600 block mb-1">Supplier</label>
                <select name="supplier_id" class="border rounded-lg px-3 py-2 text-sm min-w-[200px]">
                    <option value="">— Pilih —</option>
                    @foreach ($payables as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ format_rupiah($s->balance) }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-600 block mb-1">Nominal (Rp)</label>
                <input type="number" name="amount" min="1" required class="border rounded-lg px-3 py-2 text-sm w-36">
            </div>
            <div>
                <label class="text-xs text-slate-600 block mb-1">Metode</label>
                <select name="method" class="border rounded-lg px-3 py-2 text-sm">
                    <option value="cash">Tunai</option>
                    <option value="transfer">Transfer</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-600 block mb-1">Catatan</label>
                <input type="text" name="notes" class="border rounded-lg px-3 py-2 text-sm w-48">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm hover:bg-blue-800">Simpan</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border overflow-hidden">
        <div class="p-4 border-b font-semibold">Riwayat Pembayaran Terakhir</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3">Pihak</th>
                    <th class="px-4 py-3">Nominal</th>
                    <th class="px-4 py-3">Metode</th>
                    <th class="px-4 py-3">Catatan</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($recentPayments as $payment)
                    <tr>
                        <td class="px-4 py-3">{{ $payment->date->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $payment->type->label() }}</td>
                        <td class="px-4 py-3">{{ $payment->partyName() }}</td>
                        <td class="px-4 py-3 font-medium {{ (float) $payment->amount < 0 ? 'text-green-700' : 'text-red-700' }}">
                            {{ (float) $payment->amount < 0 ? '' : '+' }}{{ format_rupiah(abs($payment->amount)) }}
                        </td>
                        <td class="px-4 py-3">{{ $payment->method->label() }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $payment->notes ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada riwayat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
