<?php

namespace App\Services;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\DiningTable;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Outlet;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private CartService $cartService,
        private ModifierService $modifierService,
        private InventoryService $inventory,
        private LoyaltyService $loyalty
    ) {}

    public function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'ORD-' . $date . '-';

        $last = Order::where('order_number', 'like', $prefix . '%')
            ->orderByDesc('order_number')
            ->value('order_number');

        $seq = $last ? ((int) substr($last, -3)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    public function createFromCart(Outlet $outlet, array $customer): Order
    {
        $cart = $this->cartService->get($outlet);

        if (empty($cart)) {
            throw new \RuntimeException('Keranjang kosong.');
        }

        return DB::transaction(function () use ($outlet, $customer, $cart) {
            $order = Order::create([
                'outlet_id' => $outlet->id,
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $customer['name'],
                'customer_phone' => $customer['phone'],
                'notes' => $customer['notes'] ?? null,
                'status' => OrderStatus::Pending->value,
                'source' => OrderSource::Customer->value,
                'total' => $this->sumCartLines($cart),
            ]);

            $this->appendLines($order, array_values($cart));
            $this->cartService->clear($outlet);

            $order = $order->load('items');
            app(NotificationService::class)->queueOrderEvent($order, NotificationService::EVENT_NEW_ORDER);

            return $order;
        });
    }

    public function getOrCreateOpenBill(DiningTable $table, OrderSource $source, ?User $user = null): Order
    {
        $existing = $table->activeBill();

        if ($existing) {
            return $existing;
        }

        return Order::create([
            'outlet_id' => $table->outlet_id,
            'dining_table_id' => $table->id,
            'order_number' => $this->generateOrderNumber(),
            'customer_name' => $table->name,
            'customer_phone' => '-',
            'status' => OrderStatus::Open->value,
            'source' => $source->value,
            'created_by_user_id' => $user?->id,
            'total' => 0,
        ]);
    }

    public function appendCartToOpenBill(DiningTable $table, OrderSource $source, ?User $user = null): Order
    {
        $cart = $this->cartService->get($outlet = $table->outlet, $table);

        if (empty($cart)) {
            throw new \RuntimeException('Keranjang kosong.');
        }

        return DB::transaction(function () use ($table, $source, $user, $cart, $outlet) {
            $order = $this->getOrCreateOpenBill($table, $source, $user);
            $this->appendLines($order, array_values($cart));
            $this->recalculateTotal($order);
            $this->cartService->clear($outlet, $table);

            return $order->fresh('items');
        });
    }

    public function appendMenuItem(Order $order, MenuItem $item, int $qty, ?string $note = null, array $optionIds = []): Order
    {
        if (in_array($order->status, [OrderStatus::Paid->value, OrderStatus::Cancelled->value, OrderStatus::Completed->value], true)) {
            throw new \RuntimeException('Bill sudah ditutup.');
        }

        $line = $this->modifierService->buildCartLine($item, $qty, $optionIds, $note);

        return DB::transaction(function () use ($order, $line) {
            $this->createOrderItemFromLine($order, $line);
            return $this->recalculateTotal($order);
        });
    }

    public function submitOpenBill(Order $order): Order
    {
        if ($order->status === OrderStatus::Open->value) {
            $order->update(['status' => OrderStatus::Pending->value]);
        } elseif (in_array($order->status, [OrderStatus::Cooking->value, OrderStatus::Ready->value], true)) {
            $order->update([
                'status' => OrderStatus::Pending->value,
                'kitchen_started_at' => null,
                'kitchen_ready_at' => null,
            ]);
        }

        return $order->fresh('items');
    }

    public function isEditableBill(Order $order): bool
    {
        return ! in_array($order->status, [OrderStatus::Paid->value, OrderStatus::Cancelled->value, OrderStatus::Completed->value], true);
    }

    public function canModifyOrderItem(OrderItem $item): bool
    {
        return $item->order
            && $this->isEditableBill($item->order)
            && ! $item->is_paid;
    }

    public function updateOrderItemQty(OrderItem $item, int $qty): Order
    {
        if (! $this->canModifyOrderItem($item)) {
            throw new \RuntimeException('Item tidak dapat diubah.');
        }

        if ($qty < 1) {
            throw new \RuntimeException('Qty minimal 1. Gunakan hapus untuk menghapus item.');
        }

        $menuItem = $item->menu_item_id ? MenuItem::find($item->menu_item_id) : null;
        if ($menuItem && $this->inventory->tracksStock($menuItem)) {
            $diff = $qty - $item->qty;
            if ($diff > 0) {
                $this->inventory->deduct($menuItem, $diff, $item->order_id);
            } elseif ($diff < 0) {
                $this->inventory->restore($menuItem, abs($diff), $item->order_id);
            }
        }

        $item->update(['qty' => $qty]);

        return $this->recalculateTotal($item->order);
    }

    public function removeOrderItem(OrderItem $item): Order
    {
        if (! $this->canModifyOrderItem($item)) {
            throw new \RuntimeException('Item tidak dapat dihapus.');
        }

        $order = $item->order;
        $menuItem = $item->menu_item_id ? MenuItem::find($item->menu_item_id) : null;

        if ($menuItem && $this->inventory->tracksStock($menuItem)) {
            $this->inventory->restore($menuItem, $item->qty, $order->id);
        }

        $item->delete();

        return $this->recalculateTotal($order);
    }

    public function pendingCount(?int $outletId = null): int
    {
        return $this->pendingQuery($outletId)->count();
    }

    public function pendingQuery(?int $outletId = null): Builder
    {
        return Order::with(['items', 'diningTable'])
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->whereIn('status', [
                OrderStatus::Pending->value,
                OrderStatus::Ready->value,
            ])
            ->orderByDesc('created_at');
    }

    public function kitchenQuery(?int $outletId = null): Builder
    {
        return Order::with(['items', 'diningTable'])
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->whereIn('status', array_map(fn (OrderStatus $s) => $s->value, OrderStatus::kitchenStatuses()))
            ->orderBy('created_at');
    }

    public function kitchenBoard(?int $outletId = null): array
    {
        $orders = $this->kitchenQuery($outletId)->get()->map(fn (Order $order) => $this->serializeKitchenOrder($order));

        return [
            'counts' => [
                'pending' => $orders->where('status', OrderStatus::Pending->value)->count(),
                'cooking' => $orders->where('status', OrderStatus::Cooking->value)->count(),
                'ready' => $orders->where('status', OrderStatus::Ready->value)->count(),
            ],
            'columns' => [
                'pending' => $orders->where('status', OrderStatus::Pending->value)->values(),
                'cooking' => $orders->where('status', OrderStatus::Cooking->value)->values(),
                'ready' => $orders->where('status', OrderStatus::Ready->value)->values(),
            ],
        ];
    }

    public function startCooking(Order $order): Order
    {
        if ($order->status !== OrderStatus::Pending->value) {
            throw new \RuntimeException('Hanya pesanan antrian yang bisa dimulai.');
        }

        $order->update([
            'status' => OrderStatus::Cooking->value,
            'kitchen_started_at' => now(),
        ]);

        return $order->fresh('items');
    }

    public function markKitchenReady(Order $order): Order
    {
        if ($order->status !== OrderStatus::Cooking->value) {
            throw new \RuntimeException('Hanya pesanan yang sedang dimasak yang bisa ditandai siap.');
        }

        $order->update([
            'status' => OrderStatus::Ready->value,
            'kitchen_ready_at' => now(),
        ]);

        return $order->fresh('items');
    }

    public function markServed(Order $order): Order
    {
        if (! in_array($order->status, [OrderStatus::Pending->value, OrderStatus::Cooking->value, OrderStatus::Ready->value], true)) {
            throw new \RuntimeException('Pesanan tidak dapat ditandai disajikan.');
        }

        $order->update(['status' => OrderStatus::Confirmed->value]);

        return $order->fresh('items');
    }

    public function openBillsQuery(?int $outletId = null): Builder
    {
        return Order::with(['items', 'diningTable'])
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->whereNotNull('dining_table_id')
            ->whereIn('status', [
                OrderStatus::Open->value,
                OrderStatus::Pending->value,
                OrderStatus::Cooking->value,
                OrderStatus::Ready->value,
                OrderStatus::Confirmed->value,
            ])
            ->orderByDesc('updated_at');
    }

    public function cancel(Order $order): Order
    {
        if ($order->status === OrderStatus::Cancelled->value) {
            return $order;
        }

        foreach ($order->items as $orderItem) {
            if ($orderItem->menu_item_id) {
                $menuItem = MenuItem::find($orderItem->menu_item_id);
                if ($menuItem && $this->inventory->tracksStock($menuItem)) {
                    $this->inventory->restore($menuItem, $orderItem->qty, $order->id);
                }
            }
        }

        $order->update(['status' => OrderStatus::Cancelled->value]);

        return $order->fresh('items');
    }

    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        if ($status === OrderStatus::Cancelled) {
            return $this->cancel($order);
        }

        $order->update(['status' => $status->value]);

        return $order->fresh('items');
    }

    public function recordPayment(Order $order, string $method, ?int $amountPaid = null): OrderPayment
    {
        $itemIds = $order->unpaidItems()->pluck('id')->all();

        if (empty($itemIds)) {
            throw new \RuntimeException('Tidak ada item yang perlu dibayar.');
        }

        return $this->payItems($order, $itemIds, $method, $amountPaid);
    }

    /** @param array<int> $itemIds */
    public function payItems(
        Order $order,
        array $itemIds,
        string $method,
        ?int $amountPaid = null,
        ?int $cashierShiftId = null,
        ?int $pointsToRedeem = null
    ): OrderPayment {
        return DB::transaction(function () use ($order, $itemIds, $method, $amountPaid, $cashierShiftId, $pointsToRedeem) {
            $items = $order->items()
                ->whereIn('id', $itemIds)
                ->where('is_paid', false)
                ->get();

            if ($items->count() !== count(array_unique($itemIds))) {
                throw new \RuntimeException('Item pembayaran tidak valid.');
            }

            $amount = (int) $items->sum(fn (OrderItem $item) => $item->subtotal());
            $loyaltyPoints = 0;
            $loyaltyDiscount = 0;

            if ($pointsToRedeem && $pointsToRedeem > 0) {
                $redeem = $this->loyalty->validateRedemption($order, $pointsToRedeem, $amount);
                $loyaltyPoints = $redeem['points'];
                $loyaltyDiscount = $redeem['discount'];
            }

            $netAmount = $amount - $loyaltyDiscount;
            $change = null;

            if ($method === 'cash') {
                if ($amountPaid === null || $amountPaid < $netAmount) {
                    throw new \RuntimeException('Nominal bayar kurang dari total item terpilih.');
                }
                $change = max(0, $amountPaid - $netAmount);
            }

            $now = now();
            OrderItem::whereIn('id', $items->pluck('id'))->update([
                'is_paid' => true,
                'paid_at' => $now,
            ]);

            $payment = OrderPayment::create([
                'order_id' => $order->id,
                'cashier_shift_id' => $cashierShiftId,
                'status' => OrderPayment::STATUS_PAID,
                'amount' => $netAmount,
                'loyalty_points_redeemed' => $loyaltyPoints,
                'loyalty_discount' => $loyaltyDiscount,
                'payment_method' => $method,
                'amount_paid' => $amountPaid ?? $netAmount,
                'change_amount' => $change,
                'item_ids' => $items->pluck('id')->values()->all(),
                'paid_at' => $now,
            ]);

            if ($loyaltyPoints > 0) {
                $this->loyalty->redeemForPayment($order, $payment, $loyaltyPoints, $loyaltyDiscount);
            }

            $this->loyalty->earnForPayment($payment);

            $totalPaid = ($order->amount_paid ?? 0) + ($amountPaid ?? $netAmount);

            if (! $order->fresh()->hasUnpaidItems()) {
                $order->update([
                    'payment_method' => $method,
                    'amount_paid' => $totalPaid,
                    'change_amount' => $change,
                    'paid_at' => $now,
                    'status' => OrderStatus::Paid->value,
                ]);
            } else {
                $order->update(['amount_paid' => $totalPaid]);
            }

            return $payment;
        });
    }

    /** @param array<int> $itemIds */
    public function createQrisPayment(
        Order $order,
        array $itemIds,
        ?int $cashierShiftId = null,
        ?int $pointsToRedeem = null
    ): OrderPayment {
        return DB::transaction(function () use ($order, $itemIds, $cashierShiftId, $pointsToRedeem) {
            $items = $order->items()
                ->whereIn('id', $itemIds)
                ->where('is_paid', false)
                ->get();

            if ($items->count() !== count(array_unique($itemIds))) {
                throw new \RuntimeException('Item pembayaran tidak valid.');
            }

            $amount = (int) $items->sum(fn (OrderItem $item) => $item->subtotal());
            $loyaltyPoints = 0;
            $loyaltyDiscount = 0;

            if ($pointsToRedeem && $pointsToRedeem > 0) {
                $redeem = $this->loyalty->validateRedemption($order, $pointsToRedeem, $amount);
                $loyaltyPoints = $redeem['points'];
                $loyaltyDiscount = $redeem['discount'];
            }

            return OrderPayment::create([
                'order_id' => $order->id,
                'cashier_shift_id' => $cashierShiftId,
                'status' => OrderPayment::STATUS_PENDING,
                'amount' => $amount - $loyaltyDiscount,
                'loyalty_points_redeemed' => $loyaltyPoints,
                'loyalty_discount' => $loyaltyDiscount,
                'payment_method' => 'qris',
                'item_ids' => $items->pluck('id')->values()->all(),
                'expires_at' => now()->addMinutes(15),
            ]);
        });
    }

    public function completeQrisPayment(OrderPayment $payment, ?string $transactionId = null): OrderPayment
    {
        if ($payment->isPaid()) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $transactionId) {
            $payment->refresh();

            if ($payment->isPaid()) {
                return $payment;
            }

            $order = $payment->order;
            $now = now();

            OrderItem::whereIn('id', $payment->item_ids ?? [])
                ->where('is_paid', false)
                ->update(['is_paid' => true, 'paid_at' => $now]);

            $payment->update([
                'status' => OrderPayment::STATUS_PAID,
                'midtrans_transaction_id' => $transactionId ?? $payment->midtrans_transaction_id,
                'amount_paid' => $payment->amount,
                'paid_at' => $now,
            ]);

            $totalPaid = ($order->amount_paid ?? 0) + $payment->amount;

            if (! $order->fresh()->hasUnpaidItems()) {
                $order->update([
                    'payment_method' => 'qris',
                    'amount_paid' => $totalPaid,
                    'paid_at' => $now,
                    'status' => OrderStatus::Paid->value,
                ]);
            } else {
                $order->update(['amount_paid' => $totalPaid]);
            }

            if ($payment->loyalty_points_redeemed > 0) {
                $this->loyalty->redeemForPayment(
                    $order,
                    $payment,
                    (int) $payment->loyalty_points_redeemed,
                    (int) $payment->loyalty_discount
                );
            }

            $this->loyalty->earnForPayment($payment->fresh());

            return $payment->fresh();
        });
    }

    public function failQrisPayment(OrderPayment $payment, string $status): void
    {
        if ($payment->isPaid()) {
            return;
        }

        $payment->update([
            'status' => $status === 'expire' ? OrderPayment::STATUS_EXPIRED : OrderPayment::STATUS_FAILED,
        ]);
    }

    public function todayStats(?int $outletId = null): array
    {
        $query = Order::query()
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->whereDate('created_at', today())
            ->whereIn('status', [OrderStatus::Paid->value, OrderStatus::Completed->value]);

        return [
            'orders' => (clone $query)->count(),
            'revenue' => (clone $query)->sum('total'),
        ];
    }

    public function topItemsToday(?int $outletId = null, int $limit = 5): array
    {
        return OrderItem::query()
            ->select('item_name', DB::raw('SUM(qty) as total_qty'))
            ->whereHas('order', function ($q) use ($outletId) {
                $q->whereDate('created_at', today())
                    ->whereIn('status', [OrderStatus::Paid->value, OrderStatus::Completed->value])
                    ->when($outletId, fn ($q2) => $q2->where('outlet_id', $outletId));
            })
            ->groupBy('item_name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    private function appendLines(Order $order, array $lines): void
    {
        foreach ($lines as $line) {
            $this->createOrderItemFromLine($order, $line);
        }
    }

    private function createOrderItemFromLine(Order $order, array $line): void
    {
        $modifiers = $line['modifiers'] ?? [];
        $signature = $this->modifierService->modifierSignature($modifiers);
        $displayName = $line['display_name'] ?? $this->modifierService->displayName($line['name'], $modifiers);

        $existing = $order->items()
            ->where('menu_item_id', $line['menu_item_id'])
            ->where('is_paid', false)
            ->get()
            ->first(function (OrderItem $item) use ($modifiers, $line, $signature) {
                $sameModifiers = $this->modifierService->modifierSignature($item->modifierList()) === $signature;
                $sameNote = ($item->note ?? '') === ($line['note'] ?? '');

                return $sameModifiers && $sameNote;
            });

        if ($existing) {
            if ($menuItem = MenuItem::find($line['menu_item_id'])) {
                $this->inventory->deduct($menuItem, $line['qty'], $order->id);
            }
            $existing->increment('qty', $line['qty']);

            return;
        }

        if ($menuItem = MenuItem::find($line['menu_item_id'])) {
            $this->inventory->deduct($menuItem, $line['qty'], $order->id);
        }

        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $line['menu_item_id'],
            'item_name' => $displayName,
            'qty' => $line['qty'],
            'price' => $line['price'],
            'note' => $line['note'] ?? null,
            'modifiers' => $modifiers ?: null,
        ]);
    }

    private function recalculateTotal(Order $order): Order
    {
        $total = $order->items()->get()->sum(fn (OrderItem $item) => $item->subtotal());
        $order->update(['total' => $total]);

        return $order->fresh('items');
    }

    /** @param array<int, array{qty:int,price:int}> $cart */
    private function sumCartLines(array $cart): int
    {
        return collect($cart)->sum(fn ($item) => $item['qty'] * $item['price']);
    }

    private function serializeKitchenOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $order->statusEnum()->label(),
            'customer_label' => $order->displayCustomer(),
            'table_name' => $order->diningTable?->name,
            'source_label' => $order->sourceEnum()->label(),
            'notes' => $order->notes,
            'created_at' => $order->created_at?->format('H:i'),
            'wait_minutes' => $order->kitchenWaitMinutes(),
            'items' => $order->items->map(fn (OrderItem $item) => [
                'name' => $item->item_name,
                'qty' => $item->qty,
                'note' => $item->note,
                'modifiers' => $item->modifierSummary(),
            ])->values(),
        ];
    }
}
