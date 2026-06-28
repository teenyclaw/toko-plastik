@extends('layouts.app')

@section('title', ($user->exists ? 'Edit' : 'Tambah') . ' Pengguna')

@section('content')
    <h1 class="text-2xl font-bold mb-6">{{ $user->exists ? 'Edit' : 'Tambah' }} Pengguna</h1>

    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}"
          class="max-w-lg bg-white rounded-xl border p-6 space-y-4">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium mb-1">Nama</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Password {{ $user->exists ? '(kosongkan jika tidak diubah)' : '' }}</label>
            <input type="password" name="password" {{ $user->exists ? '' : 'required' }} class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Konfirmasi Password</label>
            <input type="password" name="password_confirmation" class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Role</label>
            <select name="role" class="w-full border rounded-lg px-3 py-2 text-sm">
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
                Akun aktif
            </label>
        </div>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm">Simpan</button>
            <a href="{{ route('users.index') }}" class="px-4 py-2 border rounded-lg text-sm">Batal</a>
        </div>
    </form>
@endsection
