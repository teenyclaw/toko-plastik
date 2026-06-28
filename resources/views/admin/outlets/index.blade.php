@extends('layouts.app')

@section('title', 'Cabang / Outlet')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Cabang / Outlet</h1>
    <a href="{{ route('admin.outlets.create') }}" class="bg-slate-900 text-white px-4 py-2 rounded-lg text-sm">+ Tambah Cabang</a>
</div>

<div class="bg-white rounded-xl border overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="p-3">Nama</th>
                <th class="p-3">Slug</th>
                <th class="p-3">Katalog</th>
                <th class="p-3">Status</th>
                <th class="p-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($outlets as $outlet)
                <tr class="border-t">
                    <td class="p-3 font-medium">{{ $outlet->name }}</td>
                    <td class="p-3 font-mono text-xs">{{ $outlet->slug }}</td>
                    <td class="p-3"><a href="{{ $outlet->catalogUrl() }}" target="_blank" class="text-blue-600">/o/{{ $outlet->slug }}</a></td>
                    <td class="p-3">
                        @if($outlet->is_active)
                            <span class="text-emerald-600">Aktif</span>
                        @else
                            <span class="text-slate-400">Nonaktif</span>
                        @endif
                    </td>
                    <td class="p-3 text-right space-x-3">
                        <a href="{{ route('admin.menu-copy.index') }}?from={{ $outlet->id }}" class="text-slate-600">Copy menu</a>
                        <a href="{{ route('admin.outlets.edit', $outlet) }}" class="text-blue-600">Kelola</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-slate-500">Belum ada cabang.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
