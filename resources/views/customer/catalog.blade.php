@extends('customer.layout')

@section('title', 'Menu')

@section('content')
@if(isset($table))
    <div class="bg-purple-50 border border-purple-200 rounded-xl p-3 mb-4 text-sm text-purple-900">
        Anda memesan dari <strong>{{ $table->name }}</strong>.
        @if(isset($openBill) && $openBill)
            Bill aktif: <span class="font-mono">{{ $openBill->order_number }}</span> · {{ $openBill->formattedTotal() }}
        @endif
    </div>
@else
    <p class="text-sm text-stone-600 mb-4">Pilih menu favorit Anda, lalu checkout.</p>
@endif

@foreach($categories as $category)
    @if($category->menuItems->isNotEmpty())
        <section class="mb-6">
            <h2 class="font-semibold text-base mb-3">{{ $category->name }}</h2>
            <div class="space-y-3">
                @foreach($category->menuItems as $item)
                    @include('customer.partials.menu-item', ['item' => $item])
                @endforeach
            </div>
        </section>
    @endif
@endforeach

@if($uncategorized->isNotEmpty())
    <section class="mb-6">
        <h2 class="font-semibold text-base mb-3">Lainnya</h2>
        <div class="space-y-3">
            @foreach($uncategorized as $item)
                @include('customer.partials.menu-item', ['item' => $item])
            @endforeach
        </div>
    </section>
@endif

@if($categories->flatMap->menuItems->isEmpty() && $uncategorized->isEmpty())
    <div class="text-center py-12 text-stone-500">Belum ada menu tersedia.</div>
@endif
@endsection
