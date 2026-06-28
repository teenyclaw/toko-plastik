<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        $customers = Customer::query()->orderBy('name')->get();

        return view('pos.index', compact('customers'));
    }

    public function products(Request $request): JsonResponse
    {
        $search = $request->string('q')->toString();

        $products = Product::query()
            ->active()
            ->with(['category', 'unit'])
            ->search($search)
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'barcode' => $p->barcode,
                'name' => $p->name,
                'category' => $p->category->name,
                'unit_id' => $p->unit_id,
                'unit' => $p->unit->abbreviation,
                'sell_price' => (float) $p->sell_price,
                'stock' => (float) $p->stock,
            ]);

        return response()->json(['data' => $products]);
    }
}
