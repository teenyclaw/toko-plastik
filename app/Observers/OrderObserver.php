<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\NotificationService;
use App\Services\RealtimeService;

class OrderObserver
{
    public function __construct(
        private RealtimeService $realtime,
        private NotificationService $notifications
    ) {}

    public function saved(Order $order): void
    {
        $this->realtime->bump($order->outlet_id);
    }

    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        if ($order->status === OrderStatus::Pending->value) {
            $this->notifications->queueOrderEvent($order, NotificationService::EVENT_NEW_ORDER);
        }

        if ($order->status === OrderStatus::Ready->value) {
            $this->notifications->queueOrderEvent($order, NotificationService::EVENT_ORDER_READY);
        }
    }

    public function deleted(Order $order): void
    {
        $this->realtime->bump($order->outlet_id);
    }
}
