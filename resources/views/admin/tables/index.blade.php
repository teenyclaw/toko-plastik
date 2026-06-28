@extends('layouts.app')

@section('title', 'Meja & QR')

@section('content')
<h1 class="text-2xl font-bold mb-6">Manajemen Meja</h1>

<div class="grid lg:grid-cols-3 gap-6">
    <form method="POST" action="{{ route('admin.tables.store') }}" class="bg-white rounded-xl border p-5 space-y-3 lg:col-span-1 h-fit">
        @csrf
        <h2 class="font-semibold">Tambah Meja</h2>
        <div><label class="text-sm">Nama meja</label><input type="text" name="name" placeholder="Meja 1" required class="w-full border rounded-lg px-3 py-2 mt-1"></div>
        <div><label class="text-sm">Urutan</label><input type="number" name="sort_order" value="0" min="0" class="w-full border rounded-lg px-3 py-2 mt-1"></div>
        <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-lg w-full">Tambah</button>
    </form>

    <div class="lg:col-span-2 space-y-4">
        @forelse($tables as $table)
            @php $bill = $table->activeBill(); @endphp
            <div class="bg-white rounded-xl border p-4">
                <div class="flex flex-wrap gap-4 justify-between items-start">
                    <form method="POST" action="{{ route('admin.tables.update', $table) }}" class="flex-1 grid sm:grid-cols-3 gap-2 items-end min-w-[240px]">
                        @csrf @method('PUT')
                        <div><label class="text-xs text-slate-500">Nama</label><input type="text" name="name" value="{{ $table->name }}" required class="w-full border rounded-lg px-3 py-2 text-sm"></div>
                        <div><label class="text-xs text-slate-500">Urutan</label><input type="number" name="sort_order" value="{{ $table->sort_order }}" min="0" class="w-full border rounded-lg px-3 py-2 text-sm"></div>
                        <div class="flex items-center gap-3">
                            <label class="text-sm inline-flex items-center gap-1"><input type="checkbox" name="is_active" value="1" {{ $table->is_active ? 'checked' : '' }}> Aktif</label>
                            <button type="submit" class="text-sm border px-3 py-2 rounded-lg">Simpan</button>
                        </div>
                    </form>
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('admin.tables.regenerate-token', $table) }}">@csrf
                            <button type="submit" class="text-xs border px-2 py-1 rounded">Reset QR</button>
                        </form>
                        @if(!$bill)
                            <form method="POST" action="{{ route('admin.tables.destroy', $table) }}" onsubmit="return confirm('Hapus meja?')">@csrf @method('DELETE')
                                <button type="submit" class="text-xs border border-red-200 text-red-600 px-2 py-1 rounded">Hapus</button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="mt-4 grid md:grid-cols-2 gap-4 items-start">
                    <div>
                        <div class="text-xs text-slate-500 mb-1">Link QR Meja</div>
                        <input type="text" value="{{ $table->qrUrl() }}" readonly class="w-full border rounded-lg px-3 py-2 text-xs">
                        <a href="{{ $table->qrUrl() }}" target="_blank" class="text-xs text-blue-600 mt-1 inline-block">Buka →</a>
                    </div>
                    <div class="flex justify-center p-3 bg-slate-50 rounded-xl">
                        <div id="qr-table-{{ $table->id }}"></div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border p-8 text-center text-slate-500">Belum ada meja.</div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
@foreach($tables as $table)
new QRCode(document.getElementById('qr-table-{{ $table->id }}'), {
    text: @json($table->qrUrl()),
    width: 120,
    height: 120,
});
@endforeach
</script>
@endpush
