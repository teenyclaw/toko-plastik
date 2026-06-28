@extends('layouts.app')

@section('title', ($expense->exists ? 'Edit' : 'Tambah') . ' Beban')

@section('content')
    <h1 class="text-2xl font-bold mb-6">{{ $expense->exists ? 'Edit' : 'Tambah' }} Beban Operasional</h1>

    <form method="POST" action="{{ $expense->exists ? route('expenses.update', $expense) : route('expenses.store') }}"
          class="max-w-lg bg-white rounded-xl border p-6 space-y-4">
        @csrf
        @if ($expense->exists) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium mb-1">Judul</label>
            <input type="text" name="title" value="{{ old('title', $expense->title) }}" required
                   placeholder="Contoh: Bayar listrik bulan ini" class="w-full border rounded-lg px-3 py-2 text-sm">
            @error('title')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Nominal (Rp)</label>
            <input type="number" name="amount" min="1" step="1" value="{{ old('amount', $expense->amount) }}" required
                   class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Kategori</label>
            <select name="category" class="w-full border rounded-lg px-3 py-2 text-sm">
                <option value="">— Pilih kategori —</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}" @selected(old('category', $expense->category) === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Tanggal</label>
            <input type="date" name="date" value="{{ old('date', ($expense->date ?? now())->format('Y-m-d')) }}" required
                   class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Catatan (opsional)</label>
            <textarea name="notes" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('notes', $expense->notes) }}</textarea>
        </div>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm">Simpan</button>
            <a href="{{ route('expenses.index') }}" class="px-4 py-2 border rounded-lg text-sm">Batal</a>
        </div>
    </form>
@endsection
