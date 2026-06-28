<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\OrderPayment;
use App\Services\MidtransService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __construct(
        private MidtransService $midtrans,
        private OrderService $orderService
    ) {}

    public function __invoke(Request $request)
    {
        $payload = $request->all();

        if (! $this->midtrans->verifyNotificationSignature($payload)) {
            Log::warning('Midtrans webhook signature invalid', ['order_id' => $payload['order_id'] ?? null]);

            return response()->json(['message' => 'invalid signature'], 403);
        }

        $payment = OrderPayment::where('midtrans_order_id', $payload['order_id'] ?? '')->first();

        if (! $payment) {
            return response()->json(['message' => 'ok']);
        }

        $status = $payload['transaction_status'] ?? null;

        if ($this->midtrans->isPaidStatus($status)) {
            $this->orderService->completeQrisPayment($payment, $payload['transaction_id'] ?? null);
        } elseif ($this->midtrans->isFailedStatus($status)) {
            $this->orderService->failQrisPayment($payment, $status);
        }

        return response()->json(['message' => 'ok']);
    }
}
