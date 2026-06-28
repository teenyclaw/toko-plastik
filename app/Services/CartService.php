<?php

namespace App\Services;

use App\Models\DiningTable;
use App\Models\MenuItem;
use App\Models\Outlet;

class CartService
{
    public function __construct(
        private ModifierService $modifierService,
        private InventoryService $inventory
    ) {}

    private function sessionKey(Outlet $outlet, ?int $tableId = null): string
    {
        $tableId ??= session('dining_table_' . $outlet->id);

        return $tableId
            ? 'cart_' . $outlet->id . '_t_' . $tableId
            : 'cart_' . $outlet->id;
    }

    public function get(Outlet $outlet, ?DiningTable $table = null): array
    {
        $key = $this->sessionKey($outlet, $table?->id);
        $cart = session()->get($key, []);
        $normalized = $this->normalizeCart($cart);

        if ($normalized !== $cart) {
            session()->put($key, $normalized);
        }

        return $normalized;
    }

    /** @param array<string, array<string, mixed>> $cart */
    private function normalizeCart(array $cart): array
    {
        $normalized = [];

        foreach ($cart as $sessionKey => $line) {
            if (! is_array($line)) {
                continue;
            }

            $menuItemId = (int) ($line['menu_item_id'] ?? $sessionKey);
            $note = $line['note'] ?? null;
            $optionIds = $line['option_ids'] ?? [];
            $lineKey = $line['line_key'] ?? $this->modifierService->lineKey($menuItemId, $optionIds, $note);

            $line['line_key'] = $lineKey;
            $line['menu_item_id'] = $menuItemId;
            $line['display_name'] = $line['display_name'] ?? $line['name'] ?? '';
            $line['option_ids'] = $optionIds;
            $line['modifiers'] = $line['modifiers'] ?? [];

            if (isset($normalized[$lineKey])) {
                $normalized[$lineKey]['qty'] += (int) ($line['qty'] ?? 1);
            } else {
                $normalized[$lineKey] = $line;
            }
        }

        return $normalized;
    }

    public function count(Outlet $outlet, ?DiningTable $table = null): int
    {
        return collect($this->get($outlet, $table))->sum('qty');
    }

    public function total(Outlet $outlet, ?DiningTable $table = null): int
    {
        return collect($this->get($outlet, $table))->sum(fn ($item) => $item['qty'] * $item['price']);
    }

    public function add(Outlet $outlet, MenuItem $item, int $qty, ?string $note = null, ?DiningTable $table = null, array $optionIds = []): void
    {
        $line = $this->modifierService->buildCartLine($item, $qty, $optionIds, $note);
        $cart = $this->get($outlet, $table);
        $key = $line['line_key'];

        if (isset($cart[$key])) {
            $cart[$key]['qty'] += $qty;
        } else {
            $cart[$key] = $line;
        }

        $this->assertStockForLine($item, $cart, $key, (int) $cart[$key]['qty']);

        session()->put($this->sessionKey($outlet, $table?->id), $cart);
    }

    public function update(Outlet $outlet, string $lineKey, int $qty, ?DiningTable $table = null): void
    {
        $cart = $this->get($outlet, $table);

        if (! isset($cart[$lineKey])) {
            return;
        }

        if ($qty <= 0) {
            unset($cart[$lineKey]);
        } else {
            $menuItem = MenuItem::find($cart[$lineKey]['menu_item_id']);
            if ($menuItem) {
                $this->assertStockForLine($menuItem, $cart, $lineKey, $qty);
            }
            $cart[$lineKey]['qty'] = $qty;
        }

        session()->put($this->sessionKey($outlet, $table?->id), $cart);
    }

    public function remove(Outlet $outlet, string $lineKey, ?DiningTable $table = null): void
    {
        $cart = $this->get($outlet, $table);
        unset($cart[$lineKey]);
        session()->put($this->sessionKey($outlet, $table?->id), $cart);
    }

    public function clear(Outlet $outlet, ?DiningTable $table = null): void
    {
        session()->forget($this->sessionKey($outlet, $table?->id));
    }

    public function lines(Outlet $outlet, ?DiningTable $table = null): array
    {
        return array_values($this->get($outlet, $table));
    }

    /** @param array<string, array<string, mixed>> $cart */
    private function assertStockForLine(MenuItem $item, array $cart, string $lineKey, int $lineQty): void
    {
        $otherQty = collect($cart)
            ->filter(fn ($line, $key) => $key !== $lineKey && (int) ($line['menu_item_id'] ?? 0) === $item->id)
            ->sum(fn ($line) => (int) ($line['qty'] ?? 0));

        $this->inventory->assertCanFulfill($item, $otherQty + $lineQty);
    }
}
