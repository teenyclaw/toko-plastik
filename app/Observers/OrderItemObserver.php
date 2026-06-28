<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Services\RealtimeService;

class OrderItemObserver
{
    public function __construct(private RealtimeService $realtime) {}

    public function saved(OrderItem $item): void
    {
        if ($item->order) {
            $this->realtime->bump($item->order->outlet_id);
        }
    }

    public function deleted(OrderItem $item): void
    {
        if ($item->order) {
            $this->realtime->bump($item->order->outlet_id);
        }
    }
}
