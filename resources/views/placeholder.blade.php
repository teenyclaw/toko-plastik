@extends('layouts.app')

@section('title', $title . ' — ' . config('app.name'))

@section('content')
    <div class="max-w-2xl">
        <h1 class="text-2xl font-bold text-slate-900">{{ $title }}</h1>
        <p class="text-slate-600 mt-2">Modul ini akan tersedia pada <strong>Fase {{ $phase }}</strong>.</p>
        <div class="mt-6 rounded-xl border bg-amber-50 border-amber-200 p-5 text-sm text-amber-900">
            Fase 0 hanya menyiapkan fondasi aplikasi. Kembali ke
            <a href="{{ route('dashboard') }}" class="font-medium underline">Dashboard</a>
            untuk melihat progress.
        </div>
    </div>
@endsection
