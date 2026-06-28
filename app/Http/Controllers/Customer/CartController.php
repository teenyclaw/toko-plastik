<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Services\CartService;
use App\Services\ModifierService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private ModifierService $modifierService
    ) {}

    private function outlet(string $slug): Outlet
    {
        return Outlet::where('slug', $slug)->where('is_active', true)->firstOrFail();
    }

    public function index(string $slug)
    {
        $outlet = $this->outlet($slug);
        $cart = $this->cartService->get($outlet);

        return view('customer.cart', [
            'outlet' => $outlet,
            'cart' => $cart,
            'total' => $this->cartService->total($outlet),
            'cartCount' => $this->cartService->count($outlet),
        ]);
    }

    public function add(Request $request, string $slug)
    {
        $outlet = $this->outlet($slug);

        $data = $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'qty' => 'required|integer|min:1|max:99',
            'note' => 'nullable|string|max:255',
            'option_ids' => 'nullable|array',
            'option_ids.*' => 'integer|exists:modifier_options,id',
        ]);

        $item = MenuItem::where('outlet_id', $outlet->id)
            ->where('is_available', true)
            ->with('modifierGroups')
            ->findOrFail($data['menu_item_id']);

        try {
            $this->modifierService->resolve($item, $data['option_ids'] ?? []);
            $this->cartService->add(
                $outlet,
                $item,
                $data['qty'],
                $data['note'] ?? null,
                null,
                $data['option_ids'] ?? []
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('customer.catalog', $slug)
            ->with('success', $item->name . ' ditambahkan ke keranjang.');
    }

    public function update(Request $request, string $slug, string $lineKey)
    {
        $outlet = $this->outlet($slug);

        $data = $request->validate([
            'qty' => 'required|integer|min:0|max:99',
        ]);

        try {
            $this->cartService->update($outlet, $lineKey, $data['qty']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('customer.cart', $slug);
    }

    public function remove(string $slug, string $lineKey)
    {
        $outlet = $this->outlet($slug);
        $this->cartService->remove($outlet, $lineKey);

        return redirect()->route('customer.cart', $slug);
    }
}
