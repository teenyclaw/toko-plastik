<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function increase(
        Product $product,
        User $user,
        float $quantity,
        StockMovementType $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
    ): Product {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah harus lebih dari 0.']);
        }

        $stockBefore = (float) $product->stock;
        $stockAfter = $stockBefore + $quantity;

        $product->update(['stock' => $stockAfter]);

        StockMovement::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_at' => now(),
        ]);

        return $product->fresh();
    }

    public function decrease(
        Product $product,
        User $user,
        float $quantity,
        StockMovementType $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
    ): Product {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah harus lebih dari 0.']);
        }

        $stockBefore = (float) $product->stock;

        if ($stockBefore < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Stok {$product->name} tidak cukup (tersedia {$stockBefore}).",
            ]);
        }

        $stockAfter = $stockBefore - $quantity;

        $product->update(['stock' => $stockAfter]);

        StockMovement::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_at' => now(),
        ]);

        return $product->fresh();
    }

    public function adjustTo(Product $product, User $user, float $newStock, ?string $notes = null): Product
    {
        if ($newStock < 0) {
            throw ValidationException::withMessages(['new_stock' => 'Stok tidak boleh negatif.']);
        }

        $stockBefore = (float) $product->stock;
        $delta = abs($newStock - $stockBefore);

        if ($delta === 0.0) {
            return $product;
        }

        $product->update(['stock' => $newStock]);

        StockMovement::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => StockMovementType::Adjustment,
            'quantity' => $delta,
            'stock_before' => $stockBefore,
            'stock_after' => $newStock,
            'notes' => $notes ?? 'Penyesuaian stok',
            'created_at' => now(),
        ]);

        return $product->fresh();
    }
}
