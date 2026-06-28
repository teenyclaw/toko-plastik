@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Manajemen Pengguna</h1>
            <p class="text-sm text-slate-600 mt-1">Kelola akun owner, kasir, dan gudang.</p>
        </div>
        <a href="{{ route('users.create') }}" class="px-4 py-2 bg-blue-700 text-white rounded-lg text-sm hover:bg-blue-800">+ Tambah User</a>
    </div>

    <div class="bg-white rounded-xl border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 w-28"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            {{ $user->name }}
                            @if ($user->id === auth()->id())
                                <span class="text-xs text-blue-600">(Anda)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3 capitalize">{{ $user->role->label() }}</td>
                        <td class="px-4 py-3">
                            @if ($user->is_active)
                                <span class="text-green-700">Aktif</span>
                            @else
                                <span class="text-slate-500">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 space-x-2">
                            <a href="{{ route('users.edit', $user) }}" class="text-blue-700 hover:underline">Edit</a>
                            @if ($user->id !== auth()->id())
                                <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pengguna?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
