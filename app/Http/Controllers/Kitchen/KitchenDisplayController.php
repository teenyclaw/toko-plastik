<?php

namespace App\Http\Controllers\Kitchen;

use App\Http\Controllers\Concerns\AssertsCurrentOutlet;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;

class KitchenDisplayController extends Controller
{
    use AssertsCurrentOutlet;

    public function __construct(private OrderService $orderService) {}

    public function index()
    {
        $outlet = current_outlet();
        $columns = $this->orderService->kitchenBoard($outlet->id);

        return view('kitchen.display', compact('outlet', 'columns'));
    }

    public function poll()
    {
        $outlet = current_outlet();

        return response()->json($this->orderService->kitchenBoard($outlet->id));
    }

    public function startCooking(Order $order)
    {
        $this->assertOrderInCurrentOutlet($order);
        try {
            $this->orderService->startCooking($order);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pesanan dimulai.');
    }

    public function markReady(Order $order)
    {
        $this->assertOrderInCurrentOutlet($order);
        try {
            $this->orderService->markKitchenReady($order);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pesanan siap disajikan.');
    }
}
