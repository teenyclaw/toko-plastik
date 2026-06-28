<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Concerns\AssertsCurrentOutlet;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\MidtransService;
use App\Services\OrderService;

class QrisPaymentController extends Controller
{
    use AssertsCurrentOutlet;

    public function __construct(
        private OrderService $orderService,
        private MidtransService $midtrans
    ) {}

    public function show(Order $order, OrderPayment $payment)
    {
        $this->assertOrderInCurrentOutlet($order);
        abort_unless($payment->order_id === $order->id && $payment->payment_method === 'qris', 404);

        if ($payment->isPaid()) {
            return redirect(route('pos.receipt', [$order, $payment]) . '?auto=1');
        }

        if ($payment->status === OrderPayment::STATUS_EXPIRED) {
            return redirect()->route('pos.payment', $order)->with('error', 'QRIS sudah kedaluwarsa. Buat QR baru.');
        }

        return view('pos.qris', compact('order', 'payment'));
    }

    public function status(Order $order, OrderPayment $payment)
    {
        $this->assertOrderInCurrentOutlet($order);
        abort_unless($payment->order_id === $order->id, 404);

        if ($payment->isPaid()) {
            return response()->json(['status' => 'paid', 'redirect' => route('pos.receipt', [$order, $payment]) . '?auto=1']);
        }

        if (! $payment->midtrans_order_id) {
            return response()->json(['status' => 'pending']);
        }

        try {
            $data = $this->midtrans->fetchStatus($payment->midtrans_order_id);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'pending']);
        }

        $txStatus = $data['transaction_status'] ?? null;

        if ($this->midtrans->isPaidStatus($txStatus)) {
            $this->orderService->completeQrisPayment($payment, $data['transaction_id'] ?? null);

            return response()->json([
                'status' => 'paid',
                'redirect' => route('pos.receipt', [$order, $payment->fresh()]) . '?auto=1',
            ]);
        }

        if ($this->midtrans->isFailedStatus($txStatus)) {
            $this->orderService->failQrisPayment($payment, $txStatus);

            return response()->json(['status' => 'failed', 'message' => 'Pembayaran gagal atau kedaluwarsa.']);
        }

        return response()->json(['status' => 'pending']);
    }
}
