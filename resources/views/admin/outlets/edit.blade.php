@extends('layouts.app')

@section('title', $outlet->name)

@section('content')
<a href="{{ route('admin.outlets.index') }}" class="text-sm text-blue-600 mb-4 inline-block">← Daftar cabang</a>
<h1 class="text-2xl font-bold mb-6">{{ $outlet->name }}</h1>

<div class="grid lg:grid-cols-2 gap-6">
    <form method="POST" action="{{ route('admin.outlets.update', $outlet) }}" class="bg-white rounded-xl border p-5 space-y-3">
        @csrf @method('PUT')
        @include('admin.outlets.partials.form', ['outlet' => $outlet, 'cashiers' => $cashiers, 'assignedCashierIds' => $assignedCashierIds])
        <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-lg">Simpan</button>
    </form>

    <div class="bg-white rounded-xl border p-5">
        <h2 class="font-semibold mb-3">Link Katalog & QR Code</h2>
        <p class="text-sm text-slate-500 mb-3">Bagikan link atau QR ini ke pelanggan cabang ini.</p>
        <div class="flex gap-2 mb-4">
            <input type="text" id="catalog-url" value="{{ $catalogUrl }}" readonly class="flex-1 border rounded-lg px-3 py-2 text-sm">
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('catalog-url').value);alert('Link disalin!')" class="border px-3 py-2 rounded-lg text-sm">Salin</button>
        </div>
        <div class="flex justify-center p-4 bg-slate-50 rounded-xl">
            <div id="qrcode"></div>
        </div>
        <a href="{{ $catalogUrl }}" target="_blank" class="block text-center mt-4 text-blue-600 text-sm">Buka katalog →</a>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById('qrcode'), {
    text: @json($catalogUrl),
    width: 180,
    height: 180,
});
</script>
@endpush
