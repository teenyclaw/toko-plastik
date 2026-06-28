<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Concerns\ResolvesCustomerContext;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MenuItem;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class TableController extends Controller
{
    use ResolvesCustomerContext;

    public function __construct(
        private CartService $cartService,
        private OrderService $orderService
    ) {}

    public function enter(string $slug, string $token)
    {
        $outlet = $this->resolveOutlet($slug);
        $table = $this->resolveTable($outlet, $token);
        $this->bindTableSession($outlet, $table);

        return redirect()->route('customer.table.catalog', [$slug, $token]);
    }

    public function catalog(string $slug, string $token)
    {
        $outlet = $this->resolveOutlet($slug);
        $table = $this->resolveTable($outlet, $token);
        $this->bindTableSession($outlet, $table);

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

        $openBill = $table->activeBill();

        return view('customer.catalog', [
            'outlet' => $outlet,
            'table' => $table,
            'categories' => $categories,
            'uncategorized' => $uncategorized,
            'cartCount' => $this->cartService->count($outlet, $table),
            'openBill' => $openBill,
        ]);
    }

    public function bill(string $slug, string $token)
    {
        $outlet = $this->resolveOutlet($slug);
        $table = $this->resolveTable($outlet, $token);
        $openBill = $table->activeBill()?->load('items');

        return view('customer.table-bill', [
            'outlet' => $outlet,
            'table' => $table,
            'order' => $openBill,
            'cartCount' => $this->cartService->count($outlet, $table),
        ]);
    }

    public function submitOrder(string $slug, string $token)
    {
        $outlet = $this->resolveOutlet($slug);
        $table = $this->resolveTable($outlet, $token);

        try {
            $order = $this->orderService->appendCartToOpenBill(
                $table,
                \App\Enums\OrderSource::Table
            );
            $this->orderService->submitOpenBill($order);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('customer.table.bill', [$slug, $token])
            ->with('success', 'Pesanan dikirim ke dapur. Anda masih bisa pesan lagi.');
    }
}
