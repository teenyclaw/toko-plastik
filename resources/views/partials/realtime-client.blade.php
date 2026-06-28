<script>
window.QrPosRealtime = (function () {
    const pollPosUrl = @json(route('pos.poll'));
    const pollKitchenUrl = @json(route('kitchen.poll'));
    const pollIntervalMs = @json(config('pos.realtime_poll_seconds', 15) * 1000);
    const mode = @json($realtimeMode ?? 'both');
    let lastKitchenPending = null;
    let pollTimer = null;
    let polling = false;

    function setBadge(id, count) {
        const badge = document.getElementById(id);
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    function setLiveIndicator(connected) {
        document.querySelectorAll('[data-realtime-indicator]').forEach(el => {
            el.classList.toggle('bg-emerald-500', connected);
            el.classList.toggle('bg-red-400', !connected);
            el.title = connected ? 'Live — polling setiap ' + (pollIntervalMs / 1000) + ' detik' : 'Gagal memuat — coba lagi...';
        });
    }

    function playKitchenBell() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            gain.gain.value = 0.08;
            osc.start();
            osc.stop(ctx.currentTime + 0.15);
        } catch (e) {}
    }

    function renderPosOrders(orders) {
        const container = document.getElementById('order-list');
        if (!container) return;

        if (!orders.length) {
            container.innerHTML = '<div class="col-span-full text-center py-16 text-slate-500 bg-white rounded-xl border">Tidak ada pesanan menunggu.</div>';
            return;
        }

        container.innerHTML = orders.map(o => `
            <a href="/pos/orders/${o.id}" class="block bg-white rounded-xl border p-4 hover:border-amber-400 transition ${o.status === 'ready' ? 'border-emerald-300 ring-1 ring-emerald-100' : ''}">
                <div class="flex justify-between items-start mb-2">
                    <span class="font-mono font-bold text-amber-700">${o.order_number}</span>
                    <span class="text-xs text-slate-500">${o.created_at}</span>
                </div>
                <div class="font-medium">${o.customer_name}</div>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs px-1.5 py-0.5 rounded bg-slate-100">${o.status_label}</span>
                    ${o.table_name ? `<span class="text-xs text-purple-600">${o.table_name}</span>` : `<span class="text-sm text-slate-500">${o.customer_phone}</span>`}
                </div>
                <div class="mt-3 flex justify-between text-sm">
                    <span>${o.item_count} item</span>
                    <span class="font-bold">${o.formatted_total}</span>
                </div>
            </a>
        `).join('');
    }

    function renderKitchenTicket(order, column) {
        const csrfToken = @json(csrf_token());
        const itemsHtml = order.items.map(item => {
            const mods = item.modifiers ? `<span class="text-zinc-500 text-xs block">${item.modifiers}</span>` : '';
            const note = item.note ? `<span class="text-zinc-500 text-xs block">${item.note}</span>` : '';
            return `<li class="flex justify-between gap-2 border-t border-zinc-800 pt-1"><span><strong>${item.qty}×</strong> ${item.name}${mods}${note}</span></li>`;
        }).join('');
        const notesHtml = order.notes ? `<div class="text-xs bg-zinc-800 rounded px-2 py-1 mb-2 text-amber-200">Catatan: ${order.notes}</div>` : '';

        let actionHtml = '';
        if (column === 'pending') {
            actionHtml = `<form method="POST" action="/kitchen/orders/${order.id}/start"><input type="hidden" name="_token" value="${csrfToken}"><button type="submit" class="text-sm bg-orange-600 hover:bg-orange-500 text-white px-3 py-2 rounded-lg font-medium">Mulai Masak</button></form>`;
        } else if (column === 'cooking') {
            actionHtml = `<form method="POST" action="/kitchen/orders/${order.id}/ready"><input type="hidden" name="_token" value="${csrfToken}"><button type="submit" class="text-sm bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-2 rounded-lg font-medium">Siap Saji</button></form>`;
        } else {
            actionHtml = `<span class="text-xs text-emerald-400 py-2">Menunggu pelayan...</span>`;
        }

        return `<article class="rounded-xl border border-zinc-700 bg-zinc-900 p-4">
            <div class="mb-2">
                <div class="font-mono font-bold text-lg">${order.order_number}</div>
                <div class="font-medium">${order.customer_label}</div>
                <div class="text-xs text-zinc-500">${order.source_label} · ${order.created_at} · ${order.wait_minutes} mnt</div>
            </div>
            ${notesHtml}
            <ul class="text-sm space-y-1 mb-4">${itemsHtml}</ul>
            <div class="flex flex-wrap gap-2">${actionHtml}</div>
        </article>`;
    }

    function renderKitchenColumn(key, orders) {
        const el = document.getElementById('column-' + key);
        if (!el) return;
        if (!orders.length) {
            el.innerHTML = '<p class="text-sm text-zinc-600 text-center py-8">Kosong</p>';
            return;
        }
        el.innerHTML = orders.map(o => renderKitchenTicket(o, key)).join('');
    }

    function applyKitchenBadgesOnly(k) {
        const total = k.counts.pending + k.counts.cooking + k.counts.ready;
        setBadge('kitchen-badge', total);
    }

    function applyPayload(data) {
        if (data.pos) {
            setBadge('pending-badge', data.pos.count);
            renderPosOrders(data.pos.orders);
        }

        if (data.kitchen) {
            const k = data.kitchen;
            const total = k.counts.pending + k.counts.cooking + k.counts.ready;
            setBadge('kitchen-badge', total);

            const pendingEl = document.getElementById('count-pending');
            const cookingEl = document.getElementById('count-cooking');
            const readyEl = document.getElementById('count-ready');
            if (pendingEl) pendingEl.textContent = k.counts.pending;
            if (cookingEl) cookingEl.textContent = k.counts.cooking;
            if (readyEl) readyEl.textContent = k.counts.ready;

            if (lastKitchenPending !== null && k.counts.pending > lastKitchenPending) {
                playKitchenBell();
            }
            lastKitchenPending = k.counts.pending;

            renderKitchenColumn('pending', k.columns.pending);
            renderKitchenColumn('cooking', k.columns.cooking);
            renderKitchenColumn('ready', k.columns.ready);
        }
    }

    async function fetchJson(url) {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        if (!response.ok) {
            throw new Error('Poll failed: ' + response.status);
        }
        return response.json();
    }

    async function pollOnce() {
        if (polling) return;
        polling = true;
        try {
            if (mode === 'pos' || mode === 'both') {
                const posData = await fetchJson(pollPosUrl);
                applyPayload({ pos: { count: posData.count, orders: posData.orders } });
            }
            if (mode === 'kitchen' || mode === 'both') {
                const kitchenData = await fetchJson(pollKitchenUrl);
                if (mode === 'kitchen') {
                    applyPayload({ kitchen: kitchenData });
                } else {
                    applyKitchenBadgesOnly(kitchenData);
                }
            }
            setLiveIndicator(true);
        } catch (e) {
            setLiveIndicator(false);
        } finally {
            polling = false;
        }
    }

    function start() {
        clearInterval(pollTimer);
        pollOnce();
        pollTimer = setInterval(pollOnce, pollIntervalMs);
    }

    function stop() {
        clearInterval(pollTimer);
        pollTimer = null;
    }

    return { start, stop, applyPayload, pollOnce };
})();

document.addEventListener('DOMContentLoaded', () => QrPosRealtime.start());
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        QrPosRealtime.stop();
    } else {
        QrPosRealtime.start();
    }
});
</script>
