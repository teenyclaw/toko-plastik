<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $today = now()->startOfDay();

        $todaySales = Sale::query()
            ->where('status', SaleStatus::Completed)
            ->where('date', '>=', $today)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        $lowStockCount = Product::query()
            ->active()
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        $recentSales = Sale::query()
            ->with('user')
            ->where('status', SaleStatus::Completed)
            ->where('date', '>=', $today)
            ->latest('date')
            ->limit(5)
            ->get();

        $topProducts = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->where('sales.date', '>=', $today)
            ->select('products.name', DB::raw('SUM(sale_details.quantity) as qty'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'roleLabel' => $user->role->label(),
            'todayCount' => (int) ($todaySales->count ?? 0),
            'todayTotal' => (float) ($todaySales->total ?? 0),
            'lowStockCount' => $lowStockCount,
            'recentSales' => $recentSales,
            'topProducts' => $topProducts,
        ]);
    }
}
