<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Outlet;
use Illuminate\Support\Facades\Cache;

class RealtimeService
{
    private const CACHE_TTL = 86400;

    public function cacheKey(?int $outletId = null): string
    {
        return 'qr_pos_realtime_v_' . ($outletId ?? (auth()->check() ? current_outlet_id() : Outlet::query()->value('id')) ?? 0);
    }

    public function bump(?int $outletId = null): string
    {
        $version = (string) microtime(true);
        Cache::put($this->cacheKey($outletId), $version, self::CACHE_TTL);

        return $version;
    }

    public function version(?int $outletId = null): string
    {
        return (string) Cache::get($this->cacheKey($outletId), '0');
    }

    public function snapshot(?int $outletId = null): array
    {
        $orderService = app(OrderService::class);

        $posOrders = $orderService->pendingQuery($outletId)->get()->map(fn (Order $order) => [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->displayCustomer(),
            'customer_phone' => $order->customer_phone,
            'table_name' => $order->diningTable?->name,
            'status' => $order->status,
            'status_label' => $order->statusEnum()->label(),
            'total' => $order->total,
            'formatted_total' => $order->formattedTotal(),
            'item_count' => $order->items->sum('qty'),
            'created_at' => $order->created_at?->format('d/m/Y H:i'),
        ]);

        return [
            'version' => $this->version($outletId),
            'pos' => [
                'count' => $posOrders->count(),
                'orders' => $posOrders->values(),
            ],
            'kitchen' => $orderService->kitchenBoard($outletId),
        ];
    }
}
