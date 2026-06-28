<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('head')
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">
    <div class="flex min-h-screen">
        <aside class="w-64 bg-blue-900 text-white flex flex-col shrink-0">
            <div class="p-5 border-b border-blue-800">
                <div class="font-bold text-lg">{{ config('app.name') }}</div>
                <div class="text-xs text-blue-200 mt-1">POS Toko Plastik & Bahan Kue</div>
            </div>
            <nav class="flex-1 p-3 space-y-1 text-sm">
                @foreach ($navigation ?? [] as $item)
                    @php
                        $match = $item['match'] ?? $item['route'];
                        $isActive = request()->routeIs($match);
                    @endphp
                    <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                       class="flex items-center justify-between px-3 py-2 rounded-lg {{ $isActive ? 'bg-blue-800' : 'hover:bg-blue-800/70' }}">
                        <span>{{ $item['label'] }}</span>
                        @if (!empty($item['phase']))
                            <span class="text-[10px] uppercase tracking-wide text-blue-300">F{{ $item['phase'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
            <div class="p-4 border-t border-blue-800 text-xs text-blue-200">
                <div class="text-white font-medium">{{ auth()->user()->name }}</div>
                <div class="capitalize">{{ auth()->user()->role->label() }}</div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="text-red-300 hover:text-red-200">Keluar</button>
                </form>
            </div>
        </aside>
        <main class="flex-1 p-6 overflow-auto">
            @if (session('success'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
    @stack('scripts')
</body>
</html>
