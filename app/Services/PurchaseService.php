<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * @param  array<int, array{product_id: int, quantity: float, unit_price?: float}>  $items
     */
    public function store(
        User $user,
        int $supplierId,
        array $items,
        PaymentMethod $paymentMethod,
        float $paid = 0,
        float $discount = 0,
        float $tax = 0,
        ?string $notes = null,
    ): Purchase {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Daftar barang kosong.']);
        }

        $supplier = Supplier::query()->where('id', $supplierId)->where('is_active', true)->first();
        if (! $supplier) {
            throw ValidationException::withMessages(['supplier_id' => 'Supplier tidak ditemukan atau nonaktif.']);
        }

        $productIds = collect($items)->pluck('product_id')->unique()->values();
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages(['items' => 'Beberapa produk tidak ditemukan.']);
        }

        $lineItems = [];
        $subtotal = 0.0;

        foreach ($items as $index => $item) {
            $product = $products->get($item['product_id']);
            $qty = (float) $item['quantity'];

            if ($qty <= 0) {
                throw ValidationException::withMessages(["items.{$index}.quantity" => 'Jumlah harus lebih dari 0.']);
            }

            $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : (float) $product->buy_price;
            $lineTotal = $qty * $unitPrice;
            $subtotal += $lineTotal;

            $lineItems[] = [
                'product' => $product,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total' => $lineTotal,
            ];
        }

        $total = max(0, $subtotal - $discount + $tax);

        if ($paymentMethod === PaymentMethod::Cash && $paid < $total) {
            throw ValidationException::withMessages(['paid' => 'Pembayaran kurang dari total.']);
        }

        if ($paymentMethod === PaymentMethod::Tempo) {
            $paid = 0;
        } elseif ($paid === 0.0) {
            $paid = $total;
        }

        $payable = $total - $paid;

        return DB::transaction(function () use ($user, $supplier, $lineItems, $paymentMethod, $paid, $discount, $tax, $subtotal, $total, $payable, $notes) {
            $purchase = Purchase::query()->create([
                'invoice_number' => InvoiceService::generate('PO'),
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'date' => now(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'paid' => $paid,
                'payment_method' => $paymentMethod,
                'status' => PurchaseStatus::Completed,
                'notes' => $notes,
            ]);

            foreach ($lineItems as $line) {
                /** @var Product $product */
                $product = $line['product'];

                $purchase->details()->create([
                    'product_id' => $product->id,
                    'unit_id' => $product->unit_id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'total' => $line['total'],
                    'created_at' => now(),
                ]);

                $this->stockService->increase(
                    product: $product,
                    user: $user,
                    quantity: $line['quantity'],
                    type: StockMovementType::Purchase,
                    referenceType: Purchase::class,
                    referenceId: $purchase->id,
                    notes: "Pembelian {$purchase->invoice_number}",
                );

                $product->update([
                    'buy_price' => $line['unit_price'],
                    'supplier_id' => $supplier->id,
                ]);
            }

            if ($payable > 0) {
                $supplier->increment('balance', $payable);

                Payment::query()->create([
                    'type' => PaymentType::Payable,
                    'amount' => $payable,
                    'method' => $paymentMethod === PaymentMethod::Tempo ? PaymentMethod::Tempo : PaymentMethod::Cash,
                    'date' => now(),
                    'notes' => "Pembelian {$purchase->invoice_number}",
                    'supplier_id' => $supplier->id,
                    'purchase_id' => $purchase->id,
                    'created_at' => now(),
                ]);
            }

            return $purchase->load(['details.product', 'details.unit', 'supplier', 'user']);
        });
    }
}
