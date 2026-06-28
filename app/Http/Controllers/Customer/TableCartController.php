<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Concerns\ResolvesCustomerContext;
use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Services\CartService;
use App\Services\ModifierService;
use Illuminate\Http\Request;

class TableCartController extends Controller
{
    use ResolvesCustomerContext;

    public function __construct(
        private CartService $cartService,
        private ModifierService $modifierService
    ) {}

    public function index(string $slug, string $token)
    {
        $outlet = $this->resolveOutlet($slug);
        $table = $this->resolveTable($outlet, $token);
        $this->bindTableSession($outlet, $table);

        $cart = $this->cartService->get($outlet, $table);

        return view('customer.cart', [
            'outlet' => $outlet,
            'table' => $table,
            'cart' => $cart,
            'total' => $this->cartService->total($outlet, $table),
            'cartCount' => $this->cartService->count($outlet, $table),
        ]);
    }

    public function add(Request $request, string $slug, string $token)
    {
        $outlet = $this->resolveOutlet($slug);
        $table = $this->resolveTable($outlet, $token);

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
                $table,
                $data['option_ids'] ?? []
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('customer.table.catalog', [$slug, $token])
            ->with('success', $item->name . ' ditambahkan ke keranjang.');
    }

    public function update(Request $request, string $slug, string $token, string $lineKey)
    {
        $outlet = $this->resolveOutlet($slug);
        $table = $this->resolveTable($outlet, $token);

        $data = $request->validate([
            'qty' => 'required|integer|min:0|max:99',
        ]);

        try {
            $this->cartService->update($outlet, $lineKey, $data['qty'], $table);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('customer.table.cart', [$slug, $token]);
    }

    public function remove(string $slug, string $token, string $lineKey)
    {
        $outlet = $this->resolveOutlet($slug);
        $table = $this->resolveTable($outlet, $token);
        $this->cartService->remove($outlet, $lineKey, $table);

        return redirect()->route('customer.table.cart', [$slug, $token]);
    }
}
