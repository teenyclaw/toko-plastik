<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>@yield('title', 'Menu') — {{ $outlet->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>body{font-family:system-ui,-apple-system,sans-serif} [x-cloak]{display:none!important}</style>
    @stack('head')
</head>
<body class="bg-stone-50 text-stone-900 pb-24">
    <header class="sticky top-0 z-20 bg-white border-b border-stone-200 px-4 py-3 flex items-center justify-between">
        <div>
            <div class="font-bold text-lg">{{ $outlet->name }}</div>
            <div class="text-xs text-stone-500">
                @if(isset($table))
                    {{ $table->name }} · Pesan dari meja
                @else
                    Pesan online
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if(isset($table))
                <a href="{{ route('customer.table.bill', [$outlet->slug, $table->token]) }}" class="text-xs text-purple-700 border border-purple-200 px-2 py-1 rounded-lg">Bill</a>
            @endif
            <a href="{{ isset($table) ? route('customer.table.cart', [$outlet->slug, $table->token]) : route('customer.cart', $outlet->slug) }}" class="relative inline-flex items-center justify-center w-10 h-10 rounded-full bg-orange-500 text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M6 6L5 3H2"/></svg>
                @if(($cartCount ?? 0) > 0)
                    <span class="absolute -top-1 -right-1 min-w-[1.1rem] h-[1.1rem] px-1 text-[10px] font-bold bg-red-600 rounded-full flex items-center justify-center">{{ $cartCount }}</span>
                @endif
            </a>
        </div>
    </header>

    @if(session('success'))
        <div class="mx-4 mt-3 p-3 bg-green-50 text-green-800 text-sm rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mx-4 mt-3 p-3 bg-red-50 text-red-800 text-sm rounded-lg">{{ session('error') }}</div>
    @endif

    <div class="p-4">@yield('content')</div>
    @stack('scripts')
</body>
</html>
