<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    /**
     * @param  array<int, array{product_id: int, quantity: float, unit_price?: float, discount?: float}>  $items
     */
    public function checkout(User $user, array $items, PaymentMethod $paymentMethod, float $paid, ?int $customerId = null, float $discount = 0, float $tax = 0, ?string $notes = null): Sale
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Keranjang kosong.']);
        }

        if ($paymentMethod === PaymentMethod::Tempo && ! $customerId) {
            throw ValidationException::withMessages(['customer_id' => 'Pilih pelanggan untuk pembayaran tempo.']);
        }

        $productIds = collect($items)->pluck('product_id')->unique()->values();
        $products = Product::query()
            ->with('unit')
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages(['items' => 'Beberapa produk tidak ditemukan atau tidak aktif.']);
        }

        $lineItems = [];
        $subtotal = 0.0;

        foreach ($items as $index => $item) {
            $product = $products->get($item['product_id']);
            $qty = (float) $item['quantity'];

            if ($qty <= 0) {
                throw ValidationException::withMessages(["items.{$index}.quantity" => 'Jumlah harus lebih dari 0.']);
            }

            if ((float) $product->stock < $qty) {
                throw ValidationException::withMessages([
                    'items' => "Stok {$product->name} tidak cukup (tersedia {$product->stock}).",
                ]);
            }

            $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : (float) $product->sell_price;
            $lineDiscount = (float) ($item['discount'] ?? 0);
            $lineTotal = ($qty * $unitPrice) - $lineDiscount;

            if ($lineTotal < 0) {
                throw ValidationException::withMessages(["items.{$index}.discount" => 'Diskon melebihi subtotal item.']);
            }

            $subtotal += $lineTotal;

            $lineItems[] = [
                'product' => $product,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'discount' => $lineDiscount,
                'total' => $lineTotal,
            ];
        }

        $total = max(0, $subtotal - $discount + $tax);

        if ($paymentMethod === PaymentMethod::Cash && $paid < $total) {
            throw ValidationException::withMessages(['paid' => 'Pembayaran kurang dari total.']);
        }

        $customer = null;
        if ($customerId) {
            $customer = Customer::query()->find($customerId);
            if (! $customer) {
                throw ValidationException::withMessages(['customer_id' => 'Pelanggan tidak ditemukan.']);
            }
        }

        if ($paymentMethod === PaymentMethod::Tempo && $customer) {
            $newBalance = (float) $customer->balance + $total;
            if ((float) $customer->credit_limit > 0 && $newBalance > (float) $customer->credit_limit) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Melebihi limit kredit pelanggan.',
                ]);
            }
        }

        $change = $paymentMethod === PaymentMethod::Cash ? max(0, $paid - $total) : 0;

        return DB::transaction(function () use ($user, $lineItems, $paymentMethod, $paid, $customer, $discount, $tax, $subtotal, $total, $change, $notes) {
            $sale = Sale::query()->create([
                'invoice_number' => InvoiceService::generate('INV'),
                'customer_id' => $customer?->id,
                'user_id' => $user->id,
                'date' => now(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'paid' => $paymentMethod === PaymentMethod::Tempo ? 0 : $paid,
                'change_amount' => $change,
                'payment_method' => $paymentMethod,
                'status' => SaleStatus::Completed,
                'notes' => $notes,
            ]);

            foreach ($lineItems as $line) {
                /** @var Product $product */
                $product = $line['product'];
                $qty = $line['quantity'];
                $stockBefore = (float) $product->stock;
                $stockAfter = $stockBefore - $qty;

                $sale->details()->create([
                    'product_id' => $product->id,
                    'unit_id' => $product->unit_id,
                    'quantity' => $qty,
                    'unit_price' => $line['unit_price'],
                    'discount' => $line['discount'],
                    'total' => $line['total'],
                    'created_at' => now(),
                ]);

                $product->update(['stock' => $stockAfter]);

                StockMovement::query()->create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'type' => StockMovementType::Sale,
                    'quantity' => $qty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'notes' => "Penjualan {$sale->invoice_number}",
                    'created_at' => now(),
                ]);
            }

            if ($paymentMethod === PaymentMethod::Tempo && $customer) {
                $customer->increment('balance', $total);

                Payment::query()->create([
                    'type' => PaymentType::Receivable,
                    'amount' => $total,
                    'method' => PaymentMethod::Tempo,
                    'date' => now(),
                    'notes' => "Penjualan tempo {$sale->invoice_number}",
                    'customer_id' => $customer->id,
                    'sale_id' => $sale->id,
                    'created_at' => now(),
                ]);
            }

            return $sale->load(['details.product', 'details.unit', 'customer', 'user']);
        });
    }
}
