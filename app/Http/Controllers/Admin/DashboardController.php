<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InventoryService;
use App\Services\OrderService;

class DashboardController extends Controller
{
    public function __invoke(OrderService $orderService, InventoryService $inventory)
    {
        $outlet = current_outlet();
        $stats = $orderService->todayStats($outlet?->id);
        $topItems = $orderService->topItemsToday($outlet?->id);
        $lowStock = $inventory->lowStockItems($outlet?->id);

        return view('admin.dashboard', compact('outlet', 'stats', 'topItems', 'lowStock'));
    }
}
