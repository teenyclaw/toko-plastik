<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Kitchen Display') — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; }
    </style>
    @stack('head')
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen">
    <header class="border-b border-zinc-800 px-4 py-3 flex items-center justify-between sticky top-0 bg-zinc-950/95 backdrop-blur z-10">
        <div>
            <div class="font-bold text-lg">Kitchen Display</div>
            <div class="text-xs text-zinc-400">{{ $outlet->name ?? 'Dapur' }}</div>
        </div>
        <div class="flex items-center gap-4 text-sm">
            <span id="kitchen-clock" class="text-zinc-400 font-mono"></span>
            <a href="{{ route('pos.queue') }}" class="text-zinc-400 hover:text-white">← Kasir</a>
        </div>
    </header>

    @if(session('success'))
        <div class="mx-4 mt-3 p-3 bg-emerald-900/50 border border-emerald-700 text-emerald-200 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mx-4 mt-3 p-3 bg-red-900/50 border border-red-700 text-red-200 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    @yield('content')

    <script>
    function tickClock() {
        const el = document.getElementById('kitchen-clock');
        if (el) el.textContent = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    }
    tickClock();
    setInterval(tickClock, 30000);
    </script>
    @stack('scripts')
</body>
</html>
