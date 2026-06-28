<?php

namespace App\Http\Controllers\Pos;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Http\Controllers\Concerns\AssertsCurrentOutlet;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderService;
use Illuminate\Http\Request;

class TableController extends Controller
{
    use AssertsCurrentOutlet;

    public function __construct(private OrderService $orderService) {}

    public function index()
    {
        $outlet = current_outlet();
        $tables = DiningTable::where('outlet_id', $outlet->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->load(['orders' => fn ($q) => $q->whereNotIn('status', [OrderStatus::Paid->value, OrderStatus::Cancelled->value, OrderStatus::Completed->value])->latest()]);

        return view('pos.tables.index', compact('outlet', 'tables'));
    }

    public function open(DiningTable $table)
    {
        abort_unless($table->outlet_id === current_outlet_id(), 403);

        $order = $table->activeBill() ?? $this->orderService->getOrCreateOpenBill(
            $table,
            OrderSource::Waiter,
            auth()->user()
        );

        return redirect()->route('pos.tables.order', $order);
    }

    public function order(Order $order)
    {
        $this->assertOrderInCurrentOutlet($order);
        abort_unless($order->dining_table_id && $this->orderService->isEditableBill($order), 404);

        $order->load(['items', 'diningTable']);

        $outlet = $order->outlet;
        $categories = Category::where('outlet_id', $outlet->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['menuItems' => fn ($q) => $q->where('is_available', true)->orderBy('sort_order')->with('modifierGroups')])
            ->get();

        $uncategorized = MenuItem::where('outlet_id', $outlet->id)
            ->whereNull('category_id')
            ->where('is_available', true)
            ->with('modifierGroups')
            ->orderBy('sort_order')
            ->get();

        return view('pos.tables.order', compact('order', 'categories', 'uncategorized'));
    }

    public function addItem(Request $request, Order $order)
    {
        $this->assertOrderInCurrentOutlet($order);
        abort_unless($this->orderService->isEditableBill($order), 404);

        $data = $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'qty' => 'required|integer|min:1|max:99',
            'note' => 'nullable|string|max:255',
            'option_ids' => 'nullable|array',
            'option_ids.*' => 'integer|exists:modifier_options,id',
        ]);

        $item = MenuItem::where('outlet_id', $order->outlet_id)
            ->where('is_available', true)
            ->with('modifierGroups')
            ->findOrFail($data['menu_item_id']);

        try {
            $this->orderService->appendMenuItem(
                $order,
                $item,
                $data['qty'],
                $data['note'] ?? null,
                $data['option_ids'] ?? []
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $item->name . ' ditambahkan.');
    }

    public function updateItem(Request $request, Order $order, OrderItem $item)
    {
        $this->assertOrderInCurrentOutlet($order);
        abort_unless($item->order_id === $order->id, 404);

        $data = $request->validate([
            'qty' => 'required|integer|min:1|max:99',
        ]);

        try {
            $this->orderService->updateOrderItemQty($item, $data['qty']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Qty diperbarui.');
    }

    public function removeItem(Order $order, OrderItem $item)
    {
        $this->assertOrderInCurrentOutlet($order);
        abort_unless($item->order_id === $order->id, 404);

        try {
            $this->orderService->removeOrderItem($item);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Item dihapus dari bill.');
    }

    public function submit(Order $order)
    {
        $this->assertOrderInCurrentOutlet($order);
        abort_unless($this->orderService->isEditableBill($order), 404);

        if ($order->items->isEmpty()) {
            return back()->with('error', 'Bill masih kosong.');
        }

        $this->orderService->submitOpenBill($order);

        return redirect()->route('pos.orders.show', $order)
            ->with('success', 'Bill dikirim ke antrian. Meja masih terbuka untuk pesanan tambahan.');
    }

    public function close(Order $order)
    {
        $this->assertOrderInCurrentOutlet($order);
        abort_unless(
            $order->dining_table_id
            && ! in_array($order->status, [OrderStatus::Paid->value, OrderStatus::Cancelled->value, OrderStatus::Completed->value], true),
            404
        );

        if ($order->items->isEmpty()) {
            $order->update(['status' => OrderStatus::Cancelled->value]);

            return redirect()->route('pos.tables.index')->with('success', 'Bill kosong dibatalkan.');
        }

        if ($order->status === OrderStatus::Open->value) {
            $this->orderService->submitOpenBill($order);
        }

        return redirect()->route('pos.payment', $order);
    }
}
