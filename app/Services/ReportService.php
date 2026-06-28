<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Enums\SaleStatus;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function generate(string $type, Carbon $from, Carbon $to): array
    {
        return match ($type) {
            'purchases' => $this->purchases($from, $to),
            'best-sellers' => $this->bestSellers($from, $to),
            'low-stock' => $this->lowStock(),
            'profit-loss' => $this->profitLoss($from, $to),
            default => $this->sales($from, $to),
        };
    }

    public function sales(Carbon $from, Carbon $to): array
    {
        $sales = Sale::query()
            ->with(['customer', 'user'])
            ->where('status', SaleStatus::Completed)
            ->whereBetween('date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderByDesc('date')
            ->get();

        return [
            'type' => 'sales',
            'title' => 'Laporan Penjualan',
            'rows' => $sales->map(fn (Sale $s) => [
                'invoice' => $s->invoice_number,
                'date' => $s->date,
                'party' => $s->customer?->name ?? 'Umum',
                'cashier' => $s->user->name,
                'method' => $s->payment_method->label(),
                'total' => (float) $s->total,
            ]),
            'summary' => [
                'count' => $sales->count(),
                'total' => (float) $sales->sum('total'),
            ],
        ];
    }

    public function purchases(Carbon $from, Carbon $to): array
    {
        $purchases = Purchase::query()
            ->with('supplier')
            ->where('status', PurchaseStatus::Completed)
            ->whereBetween('date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderByDesc('date')
            ->get();

        return [
            'type' => 'purchases',
            'title' => 'Laporan Pembelian',
            'rows' => $purchases->map(fn (Purchase $p) => [
                'invoice' => $p->invoice_number,
                'date' => $p->date,
                'party' => $p->supplier->name,
                'method' => $p->payment_method->label(),
                'total' => (float) $p->total,
            ]),
            'summary' => [
                'count' => $purchases->count(),
                'total' => (float) $purchases->sum('total'),
            ],
        ];
    }

    public function bestSellers(Carbon $from, Carbon $to): array
    {
        $items = SaleDetail::query()
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereBetween('sales.date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->select(
                'products.name',
                DB::raw('SUM(sale_details.quantity) as qty'),
                DB::raw('SUM(sale_details.total) as total'),
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        return [
            'type' => 'best-sellers',
            'title' => 'Produk Terlaris',
            'rows' => $items->map(fn ($row) => [
                'name' => $row->name,
                'qty' => (float) $row->qty,
                'total' => (float) $row->total,
            ]),
            'summary' => [
                'count' => $items->count(),
                'total' => (float) $items->sum('total'),
            ],
        ];
    }

    public function lowStock(): array
    {
        $products = Product::query()
            ->with(['unit', 'category'])
            ->active()
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->get();

        return [
            'type' => 'low-stock',
            'title' => 'Stok Menipis',
            'rows' => $products->map(fn (Product $p) => [
                'code' => $p->code,
                'name' => $p->name,
                'category' => $p->category->name,
                'stock' => (float) $p->stock,
                'min_stock' => (float) $p->min_stock,
                'unit' => $p->unit->abbreviation,
            ]),
            'summary' => [
                'count' => $products->count(),
            ],
        ];
    }

    public function profitLoss(Carbon $from, Carbon $to): array
    {
        $dateRange = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];

        $revenue = (float) Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereBetween('date', $dateRange)
            ->sum('total');

        $cogs = (float) SaleDetail::query()
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereBetween('sales.date', $dateRange)
            ->selectRaw('SUM(sale_details.quantity * products.buy_price) as cogs')
            ->value('cogs');

        $expenses = (float) Expense::query()
            ->whereBetween('date', $dateRange)
            ->sum('amount');

        $expenseRows = Expense::query()
            ->with('user')
            ->whereBetween('date', $dateRange)
            ->orderByDesc('date')
            ->get()
            ->map(fn (Expense $e) => [
                'title' => $e->title,
                'category' => $e->category ?? '—',
                'date' => $e->date,
                'amount' => (float) $e->amount,
                'user' => $e->user->name,
            ]);

        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $expenses;

        return [
            'type' => 'profit-loss',
            'title' => 'Laba Rugi',
            'rows' => $expenseRows,
            'summary' => [
                'revenue' => $revenue,
                'cogs' => $cogs,
                'gross_profit' => $grossProfit,
                'expenses' => $expenses,
                'net_profit' => $netProfit,
            ],
        ];
    }
}
