<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Concerns\AssertsCurrentOutlet;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\LoyaltyService;
use App\Services\MidtransService;
use App\Services\OrderService;
use App\Services\ShiftService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use AssertsCurrentOutlet;

    public function __construct(
        private OrderService $orderService,
        private MidtransService $midtrans,
        private ShiftService $shiftService,
        private LoyaltyService $loyalty
    ) {}

    public function show(Order $order)
    {
        $this->assertOrderInCurrentOutlet($order);
        $order->load(['items', 'payments']);

        if (in_array($order->status, ['paid', 'cancelled'], true) && ! $order->hasUnpaidItems()) {
            return redirect()->route('pos.orders.show', $order);
        }

        $loyaltySettings = null;
        $member = null;
        $canEarnLoyalty = false;

        if ($this->loyalty->isEnabled($order->outlet) && $this->loyalty->isEligiblePhone($order->customer_phone)) {
            $loyaltySettings = $this->loyalty->settings($order->outlet);
            $member = $this->loyalty->findMember($order->outlet, $order->customer_phone);
            $canEarnLoyalty = true;
        }

        return view('pos.payment', [
            'order' => $order,
            'qrisEnabled' => $this->midtrans->isConfigured(),
            'loyaltySettings' => $loyaltySettings,
            'member' => $member,
            'canEarnLoyalty' => $canEarnLoyalty,
        ]);
    }

    public function store(Request $request, Order $order)
    {
        $this->assertOrderInCurrentOutlet($order);
        $methods = $this->midtrans->isConfigured()
            ? 'cash,transfer,qris'
            : 'cash,transfer';

        $data = $request->validate([
            'payment_method' => 'required|in:' . $methods,
            'amount_paid' => 'nullable|integer|min:0',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'integer|exists:order_items,id',
            'pay_all' => 'nullable|boolean',
            'loyalty_points' => 'nullable|integer|min:0',
        ]);

        $itemIds = $request->boolean('pay_all')
            ? $order->unpaidItems()->pluck('id')->all()
            : ($data['item_ids'] ?? []);

        if (empty($itemIds)) {
            return back()->with('error', 'Pilih minimal satu item untuk dibayar.');
        }

        $pointsToRedeem = (int) ($data['loyalty_points'] ?? 0);

        if ($data['payment_method'] === 'qris') {
            return $this->initiateQris($order, $itemIds, $pointsToRedeem);
        }

        try {
            $this->shiftService->requireOpenShift(auth()->user(), $order->outlet_id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $shift = $this->shiftService->currentShift(auth()->user(), $order->outlet_id);

        $amount = (int) $order->items()
            ->whereIn('id', $itemIds)
            ->where('is_paid', false)
            ->get()
            ->sum(fn ($item) => $item->subtotal());

        $loyaltyDiscount = 0;
        if ($pointsToRedeem > 0) {
            $loyaltyDiscount = $this->loyalty->validateRedemption($order, $pointsToRedeem, $amount)['discount'];
        }

        $netAmount = $amount - $loyaltyDiscount;

        if ($data['payment_method'] === 'cash') {
            $request->validate([
                'amount_paid' => 'required|integer|min:' . $netAmount,
            ]);
        }

        try {
            $payment = $this->orderService->payItems(
                $order,
                $itemIds,
                $data['payment_method'],
                $data['amount_paid'] ?? null,
                $shift?->id,
                $pointsToRedeem > 0 ? $pointsToRedeem : null
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $order->refresh();

        if ($order->hasUnpaidItems()) {
            return redirect()->route('pos.payment', $order)
                ->with('success', 'Pembayaran sebagian berhasil. Masih ada sisa tagihan.')
                ->with('last_payment_id', $payment->id);
        }

        return redirect(route('pos.receipt', [$order, $payment]) . '?auto=1')
            ->with('success', 'Pembayaran lunas.');
    }

    /** @param array<int> $itemIds */
    private function initiateQris(Order $order, array $itemIds, int $pointsToRedeem = 0)
    {
        try {
            $this->shiftService->requireOpenShift(auth()->user(), $order->outlet_id);
            $shift = $this->shiftService->currentShift(auth()->user(), $order->outlet_id);
            $payment = $this->orderService->createQrisPayment(
                $order,
                $itemIds,
                $shift?->id,
                $pointsToRedeem > 0 ? $pointsToRedeem : null
            );
            $charge = $this->midtrans->createQrisCharge($payment);
            $payment->update([
                'midtrans_order_id' => $charge['midtrans_order_id'],
                'midtrans_transaction_id' => $charge['transaction_id'],
                'qris_url' => $charge['qris_url'],
            ]);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.qris.show', [$order, $payment]);
    }
}
