@extends('layouts.app')

@section('title', 'Assign Cabang — ' . $user->name)

@section('content')
<a href="{{ route('admin.users.index') }}" class="text-sm text-blue-600 mb-4 inline-block">← Daftar pengguna</a>
<h1 class="text-2xl font-bold mb-1">Assign Cabang</h1>
<p class="text-sm text-slate-500 mb-6">{{ $user->name }} · {{ $user->email }}</p>

<form method="POST" action="{{ route('admin.users.update', $user) }}" class="bg-white rounded-xl border p-5 max-w-lg space-y-3">
    @csrf @method('PUT')
    <p class="text-sm text-slate-600">Pilih cabang yang boleh diakses kasir ini:</p>
    @foreach($outlets as $outlet)
        <label class="flex items-center gap-2 text-sm border rounded-lg px-3 py-2">
            <input type="checkbox" name="outlet_ids[]" value="{{ $outlet->id }}"
                @checked(in_array($outlet->id, old('outlet_ids', $assignedOutletIds)))>
            {{ $outlet->name }} <span class="text-slate-400 font-mono text-xs">/o/{{ $outlet->slug }}</span>
        </label>
    @endforeach
    @error('outlet_ids')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-lg">Simpan</button>
</form>
@endsection
