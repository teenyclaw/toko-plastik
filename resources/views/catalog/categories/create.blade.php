@extends('layouts.app')

@section('title', 'Tambah Kategori')

@section('content')
    <h1 class="text-2xl font-bold mb-6">Tambah Kategori</h1>
    @include('catalog.categories._form')
@endsection
