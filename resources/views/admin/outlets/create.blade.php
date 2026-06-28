@extends('layouts.app')

@section('title', 'Tambah Cabang')

@section('content')
<a href="{{ route('admin.outlets.index') }}" class="text-sm text-blue-600 mb-4 inline-block">← Daftar cabang</a>
<h1 class="text-2xl font-bold mb-6">Tambah Cabang</h1>

<form method="POST" action="{{ route('admin.outlets.store') }}" class="bg-white rounded-xl border p-5 space-y-3 max-w-lg">
    @csrf
    @include('admin.outlets.partials.form', ['outlet' => null, 'cashiers' => $cashiers, 'assignedCashierIds' => []])
    <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-lg">Simpan Cabang</button>
</form>
@endsection
