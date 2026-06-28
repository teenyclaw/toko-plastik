<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index()
    {
        $outlet = current_outlet();
        $items = $this->inventory->trackedItems($outlet->id);
        $lowStock = $this->inventory->lowStockItems($outlet->id);
        $recentMovements = StockMovement::query()
            ->whereHas('menuItem', fn ($q) => $q->where('outlet_id', $outlet->id))
            ->with(['menuItem', 'user'])
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.inventory.index', compact('outlet', 'items', 'lowStock', 'recentMovements'));
    }

    public function adjust(Request $request, MenuItem $menuItem)
    {
        abort_unless($menuItem->outlet_id === current_outlet_id(), 403);

        $data = $request->validate([
            'stock_qty' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $this->inventory->adjust($menuItem, $data['stock_qty'], $data['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Stok ' . $menuItem->name . ' diperbarui.');
    }
}
