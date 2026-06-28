<form method="POST" action="{{ isset($unit->id) ? route('units.update', $unit) : route('units.store') }}" class="max-w-lg bg-white rounded-xl border p-6 space-y-4">
    @csrf
    @if (isset($unit->id)) @method('PUT') @endif

    <div>
        <label class="block text-sm font-medium mb-1">Nama</label>
        <input type="text" name="name" value="{{ old('name', $unit->name ?? '') }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Singkatan</label>
        <input type="text" name="abbreviation" value="{{ old('abbreviation', $unit->abbreviation ?? '') }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>

    <div class="flex gap-2 pt-2">
        <button type="submit" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm">Simpan</button>
        <a href="{{ route('units.index') }}" class="px-4 py-2 border rounded-lg text-sm">Batal</a>
    </div>
</form>
