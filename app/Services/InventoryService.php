<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function tracksStock(MenuItem $item): bool
    {
        return (bool) $item->track_stock;
    }

    /** @return int|null null = stok tidak dilacak */
    public function availableQty(MenuItem $item): ?int
    {
        if (! $this->tracksStock($item)) {
            return null;
        }

        return max(0, (int) $item->stock_qty);
    }

    public function isLowStock(MenuItem $item): bool
    {
        if (! $this->tracksStock($item)) {
            return false;
        }

        return $item->stock_qty <= $item->low_stock_threshold;
    }

    public function isOutOfStock(MenuItem $item): bool
    {
        if (! $this->tracksStock($item)) {
            return ! $item->is_available;
        }

        return $item->stock_qty <= 0;
    }

    public function assertCanFulfill(MenuItem $item, int $qty): void
    {
        if (! $this->tracksStock($item)) {
            if (! $item->is_available) {
                throw new \RuntimeException($item->name . ' tidak tersedia.');
            }

            return;
        }

        if ($qty < 1) {
            throw new \RuntimeException('Qty tidak valid.');
        }

        if ($item->stock_qty < $qty) {
            throw new \RuntimeException(
                $item->name . ' stok tidak cukup. Tersedia: ' . $item->stock_qty . ' pcs.'
            );
        }
    }

    public function deduct(MenuItem $item, int $qty, ?int $orderId = null, ?int $userId = null): MenuItem
    {
        if (! $this->tracksStock($item) || $qty < 1) {
            return $item;
        }

        return DB::transaction(function () use ($item, $qty, $orderId, $userId) {
            $locked = MenuItem::query()->lockForUpdate()->findOrFail($item->id);
            $this->assertCanFulfill($locked, $qty);

            $locked->stock_qty -= $qty;
            $this->syncAvailability($locked);
            $locked->save();

            $this->logMovement($locked, StockMovement::TYPE_SALE, -$qty, $orderId, $userId);

            return $locked->fresh();
        });
    }

    public function restore(MenuItem $item, int $qty, ?int $orderId = null, ?int $userId = null): MenuItem
    {
        if (! $this->tracksStock($item) || $qty < 1) {
            return $item;
        }

        return DB::transaction(function () use ($item, $qty, $orderId, $userId) {
            $locked = MenuItem::query()->lockForUpdate()->findOrFail($item->id);
            $locked->stock_qty += $qty;
            $this->syncAvailability($locked);
            $locked->save();

            $this->logMovement($locked, StockMovement::TYPE_RESTORE, $qty, $orderId, $userId);

            return $locked->fresh();
        });
    }

    public function adjust(MenuItem $item, int $newQty, ?string $notes = null, ?int $userId = null): MenuItem
    {
        if ($newQty < 0) {
            throw new \RuntimeException('Stok tidak boleh negatif.');
        }

        return DB::transaction(function () use ($item, $newQty, $notes, $userId) {
            $locked = MenuItem::query()->lockForUpdate()->findOrFail($item->id);
            $locked->track_stock = true;
            $diff = $newQty - (int) $locked->stock_qty;
            $locked->stock_qty = $newQty;
            $this->syncAvailability($locked);
            $locked->save();

            if ($diff !== 0) {
                $this->logMovement($locked, StockMovement::TYPE_ADJUST, $diff, null, $userId, $notes);
            }

            return $locked->fresh();
        });
    }

    public function syncAvailability(MenuItem $item): void
    {
        if (! $this->tracksStock($item)) {
            return;
        }

        $item->is_available = $item->stock_qty > 0;
    }

    /** @return Collection<int, MenuItem> */
    public function lowStockItems(?int $outletId = null): Collection
    {
        return MenuItem::query()
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->where('track_stock', true)
            ->whereColumn('stock_qty', '<=', 'low_stock_threshold')
            ->orderBy('stock_qty')
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, MenuItem> */
    public function trackedItems(?int $outletId = null): Collection
    {
        return MenuItem::query()
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->where('track_stock', true)
            ->with('category')
            ->orderBy('name')
            ->get();
    }

    private function logMovement(
        MenuItem $item,
        string $type,
        int $quantity,
        ?int $orderId = null,
        ?int $userId = null,
        ?string $notes = null
    ): void {
        StockMovement::create([
            'menu_item_id' => $item->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_after' => $item->stock_qty,
            'order_id' => $orderId,
            'user_id' => $userId ?? auth()->id(),
            'notes' => $notes,
        ]);
    }
}
