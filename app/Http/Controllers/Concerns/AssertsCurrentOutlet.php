<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Order;
use App\Services\CurrentOutletService;

trait AssertsCurrentOutlet
{
    protected function assertOrderInCurrentOutlet(Order $order): void
    {
        app(CurrentOutletService::class)->assertOrderBelongsToCurrentOutlet($order->outlet_id);
    }
}
