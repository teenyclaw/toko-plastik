<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Enums\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchaseService)
    {
    }

    public function index(): View
    {
        $purchases = Purchase::query()
            ->with(['supplier', 'user'])
            ->withCount('details')
            ->latest('date')
            ->paginate(20);

        return view('inventory.purchases.index', compact('purchases'));
    }

    public function create(): View
    {
        return view('inventory.purchases.create', [
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,tempo'],
            'paid' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $purchase = $this->purchaseService->store(
            user: $request->user(),
            supplierId: (int) $data['supplier_id'],
            items: $data['items'],
            paymentMethod: PaymentMethod::from($data['payment_method']),
            paid: (float) ($data['paid'] ?? 0),
            discount: (float) ($data['discount'] ?? 0),
            tax: (float) ($data['tax'] ?? 0),
            notes: $data['notes'] ?? null,
        );

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Pembelian berhasil dicatat.');
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['details.product', 'details.unit', 'supplier', 'user']);

        return view('inventory.purchases.show', compact('purchase'));
    }

    public function products(Request $request): \Illuminate\Http\JsonResponse
    {
        $search = $request->string('q')->toString();
        $supplierId = $request->integer('supplier_id');

        $query = Product::query()->with(['unit', 'supplier'])->orderBy('name');

        if ($supplierId) {
            $query->where(function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId)->orWhereNull('supplier_id');
            });
        }

        $products = $query->search($search)->limit(50)->get()->map(fn (Product $p) => [
            'id' => $p->id,
            'code' => $p->code,
            'name' => $p->name,
            'unit' => $p->unit->abbreviation,
            'buy_price' => (float) $p->buy_price,
            'stock' => (float) $p->stock,
        ]);

        return response()->json(['data' => $products]);
    }
}
