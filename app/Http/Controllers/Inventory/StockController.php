<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    public function index(Request $request): View
    {
        $search = $request->string('q')->toString();
        $filter = $request->string('filter')->toString();

        $productsQuery = Product::query()
            ->with(['category', 'unit'])
            ->search($search)
            ->orderBy('name');

        if ($filter === 'low') {
            $productsQuery->whereColumn('stock', '<=', 'min_stock');
        }

        $products = $productsQuery->paginate(20)->withQueryString();

        $movements = StockMovement::query()
            ->with(['product', 'user'])
            ->latest('created_at')
            ->paginate(15, ['*'], 'movement_page');

        $stats = [
            'total_products' => Product::query()->count(),
            'low_stock' => Product::query()->whereColumn('stock', '<=', 'min_stock')->count(),
            'out_of_stock' => Product::query()->where('stock', '<=', 0)->count(),
        ];

        return view('inventory.stock.index', compact('products', 'search', 'filter', 'movements', 'stats'));
    }

    public function adjust(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'mode' => ['required', 'in:in,out,set'],
            'quantity' => ['nullable', 'numeric', 'min:0.001'],
            'new_stock' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::query()->findOrFail($data['product_id']);
        $user = $request->user();
        $notes = $data['notes'] ?? null;

        match ($data['mode']) {
            'in' => $this->stockService->increase(
                $product,
                $user,
                (float) $data['quantity'],
                \App\Enums\StockMovementType::In,
                notes: $notes ?? 'Stok masuk manual',
            ),
            'out' => $this->stockService->decrease(
                $product,
                $user,
                (float) $data['quantity'],
                \App\Enums\StockMovementType::Out,
                notes: $notes ?? 'Stok keluar manual',
            ),
            'set' => $this->stockService->adjustTo(
                $product,
                $user,
                (float) $data['new_stock'],
                $notes ?? 'Penyesuaian stok',
            ),
        };

        return back()->with('success', "Stok {$product->name} berhasil diperbarui.");
    }
}
