<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->toString();

        $products = Product::query()
            ->with(['category', 'unit', 'supplier'])
            ->search($search)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('catalog.products.index', compact('products', 'search'));
    }

    public function create(): View
    {
        return view('catalog.products.form', [
            'product' => new Product(['is_active' => true, 'stock' => 0, 'min_stock' => 0]),
            'categories' => Category::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Product::query()->create($data);

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        return view('catalog.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->saleDetails()->exists()) {
            return back()->with('error', 'Produk sudah pernah dijual, nonaktifkan saja.');
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produk dihapus.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $codeRule = ['required', 'string', 'max:50'];
        $codeRule[] = $product
            ? 'unique:products,code,'.$product->id
            : 'unique:products,code';

        $barcodeRule = ['nullable', 'string', 'max:50'];
        if ($product) {
            $barcodeRule[] = 'unique:products,barcode,'.$product->id;
        } else {
            $barcodeRule[] = 'unique:products,barcode';
        }

        return $request->validate([
            'code' => $codeRule,
            'barcode' => $barcodeRule,
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'buy_price' => ['required', 'numeric', 'min:0'],
            'sell_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
