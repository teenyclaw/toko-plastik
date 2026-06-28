<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Concerns\ResolvesCustomerContext;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Services\CartService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    use ResolvesCustomerContext;

    public function __construct(private CartService $cartService) {}

    public function show(string $slug)
    {
        $outlet = Outlet::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $this->clearTableSession($outlet);

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

        return view('customer.catalog', [
            'outlet' => $outlet,
            'categories' => $categories,
            'uncategorized' => $uncategorized,
            'cartCount' => $this->cartService->count($outlet),
        ]);
    }
}
