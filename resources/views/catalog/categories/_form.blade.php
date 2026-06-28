<form method="POST" action="{{ isset($category->id) ? route('categories.update', $category) : route('categories.store') }}" class="max-w-lg bg-white rounded-xl border p-6 space-y-4">
    @csrf
    @if (isset($category->id)) @method('PUT') @endif

    <div>
        <label class="block text-sm font-medium mb-1">Nama</label>
        <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}" required
               class="w-full border rounded-lg px-3 py-2 text-sm @error('name') border-red-500 @enderror">
        @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Tipe</label>
        <select name="type" class="w-full border rounded-lg px-3 py-2 text-sm">
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(old('type', optional($category->type)->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex gap-2 pt-2">
        <button type="submit" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm">Simpan</button>
        <a href="{{ route('categories.index') }}" class="px-4 py-2 border rounded-lg text-sm">Batal</a>
    </div>
</form>
