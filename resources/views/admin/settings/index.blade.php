@extends('layouts.app')

@section('title', 'Pengaturan Toko')

@section('content')
    <h1 class="text-2xl font-bold mb-6">Pengaturan Toko</h1>

    <form method="POST" action="{{ route('settings.update') }}" class="max-w-xl bg-white rounded-xl border p-6 space-y-4">
        @csrf
        @method('PUT')

        @foreach ($keys as $key => $meta)
            <div>
                <label class="block text-sm font-medium mb-1">{{ $meta['label'] }}</label>
                @if ($key === 'store_address' || $key === 'receipt_footer')
                    <textarea name="{{ $key }}" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm">{{ old($key, $values[$key]) }}</textarea>
                @else
                    <input type="text" name="{{ $key }}" value="{{ old($key, $values[$key]) }}"
                           class="w-full border rounded-lg px-3 py-2 text-sm">
                @endif
            </div>
        @endforeach

        <p class="text-xs text-slate-500">Pengaturan ini dipakai di struk penjualan dan tampilan toko.</p>

        <button type="submit" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm hover:bg-blue-800">Simpan</button>
    </form>
@endsection
