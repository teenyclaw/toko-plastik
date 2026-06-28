<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Outlet;
use App\Services\MidtransService;
use App\Services\OrderService;

class QrisController extends Controller
{
    public function __construct(
        private MidtransService $midtrans,
        private OrderService $orderService
    ) {}

    public function show(string $slug, string $orderNumber)
    {
        $outlet = Outlet::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $order = Order::where('outlet_id', $outlet->id)->where('order_number', $orderNumber)->firstOrFail();
        $payment = $order->payments()
            ->where('payment_method', 'qris')
            ->where('status', OrderPayment::STATUS_PENDING)
            ->latest()
            ->first();

        if (! $payment) {
            if ($order->status === 'paid') {
                return redirect()->route('customer.thanks', [$slug, $orderNumber])
                    ->with('success', 'Pembayaran sudah lunas.');
            }

            abort(404);
        }

        if ($payment->isPaid()) {
            return redirect()->route('customer.thanks', [$slug, $orderNumber]);
        }

        return view('customer.qris', compact('outlet', 'order', 'payment'));
    }

    public function status(string $slug, string $orderNumber, OrderPayment $payment)
    {
        $outlet = Outlet::where('slug', $slug)->where('is_active', true)->firstOrFail();
        abort_unless($payment->order->order_number === $orderNumber && $payment->order->outlet_id === $outlet->id, 404);

        if ($payment->isPaid()) {
            return response()->json([
                'status' => 'paid',
                'redirect' => route('customer.thanks', [$slug, $orderNumber]),
            ]);
        }

        if (! $payment->midtrans_order_id) {
            return response()->json(['status' => 'pending']);
        }

        try {
            $data = $this->midtrans->fetchStatus($payment->midtrans_order_id);
        } catch (\RuntimeException) {
            return response()->json(['status' => 'pending']);
        }

        if ($this->midtrans->isPaidStatus($data['transaction_status'] ?? null)) {
            $this->orderService->completeQrisPayment($payment, $data['transaction_id'] ?? null);

            return response()->json([
                'status' => 'paid',
                'redirect' => route('customer.thanks', [$slug, $orderNumber]),
            ]);
        }

        if ($this->midtrans->isFailedStatus($data['transaction_status'] ?? null)) {
            $this->orderService->failQrisPayment($payment, $data['transaction_status']);

            return response()->json(['status' => 'failed']);
        }

        return response()->json(['status' => 'pending']);
    }
}
