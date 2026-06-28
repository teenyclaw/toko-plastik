<?php

namespace App\Http\Controllers\Pos;

use App\Enums\OrderStatus;
use App\Http\Controllers\Concerns\AssertsCurrentOutlet;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    use AssertsCurrentOutlet;

    public function __construct(private OrderService $orderService) {}

    public function index()
    {
        $outlet = current_outlet();
        $orders = $this->orderService->pendingQuery($outlet->id)->get();

        return view('pos.queue', compact('outlet', 'orders'));
    }

    public function poll()
    {
        $outlet = current_outlet();

        $orders = $this->orderService->pendingQuery($outlet->id)->get()->map(fn (Order $order) => [
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

        return response()->json([
            'count' => $orders->count(),
            'orders' => $orders,
        ]);
    }

    public function show(Order $order)
    {
        $this->assertOrderInCurrentOutlet($order);
        $order->load('items');

        return view('pos.show', compact('order'));
    }

    public function confirm(Order $order)
    {
        $this->assertOrderInCurrentOutlet($order);
        try {
            $this->orderService->markServed($order);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pesanan ditandai sudah disajikan.');
    }

    public function complete(Order $order)
    {
        $this->assertOrderInCurrentOutlet($order);
        $this->orderService->updateStatus($order, OrderStatus::Completed);

        return back()->with('success', 'Pesanan selesai.');
    }

    public function cancel(Order $order)
    {
        $this->assertOrderInCurrentOutlet($order);
        $this->orderService->updateStatus($order, OrderStatus::Cancelled);

        return redirect()->route('pos.queue')->with('success', 'Pesanan dibatalkan.');
    }
}
