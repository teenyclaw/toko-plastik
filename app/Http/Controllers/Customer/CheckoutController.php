<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Outlet;
use App\Services\CartService;
use App\Services\LoyaltyService;
use App\Services\MidtransService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
        private MidtransService $midtrans,
        private LoyaltyService $loyalty
    ) {}

    public function show(string $slug)
    {
        $outlet = Outlet::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $cart = $this->cartService->get($outlet);

        if (empty($cart)) {
            return redirect()->route('customer.catalog', $slug)
                ->with('error', 'Keranjang masih kosong.');
        }

        $loyaltySettings = $this->loyalty->isEnabled($outlet) ? $this->loyalty->settings($outlet) : null;

        return view('customer.checkout', [
            'outlet' => $outlet,
            'cart' => $cart,
            'total' => $this->cartService->total($outlet),
            'cartCount' => $this->cartService->count($outlet),
            'qrisEnabled' => $this->midtrans->isConfigured(),
            'loyaltySettings' => $loyaltySettings,
        ]);
    }

    public function loyaltyLookup(Request $request, string $slug)
    {
        $outlet = Outlet::where('slug', $slug)->where('is_active', true)->firstOrFail();

        if (! $this->loyalty->isEnabled($outlet)) {
            return response()->json(['enabled' => false]);
        }

        $phone = $request->get('phone', '');

        if (! $this->loyalty->isEligiblePhone($phone)) {
            return response()->json([
                'enabled' => true,
                'eligible' => false,
                'message' => 'Masukkan nomor telepon valid untuk cek poin.',
            ]);
        }

        $settings = $this->loyalty->settings($outlet);
        $member = $this->loyalty->findMember($outlet, $phone);
        $total = $this->cartService->total($outlet);
        $maxRedeem = $member
            ? $this->loyalty->maxRedeemPoints($member, $total, $settings)
            : 0;

        return response()->json([
            'enabled' => true,
            'eligible' => true,
            'points' => $member?->points ?? 0,
            'min_redeem' => $settings->min_redeem_points,
            'max_redeem' => $maxRedeem,
            'rp_per_point' => $settings->redeem_rp_per_point,
        ]);
    }

    public function store(Request $request, string $slug)
    {
        $outlet = Outlet::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'required|string|max:20',
            'notes' => 'nullable|string|max:500',
            'pay_method' => 'nullable|in:counter,qris',
            'loyalty_points' => 'nullable|integer|min:0',
        ]);

        try {
            $order = $this->orderService->createFromCart($outlet, [
                'name' => $data['customer_name'],
                'phone' => $data['customer_phone'],
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pointsToRedeem = (int) ($data['loyalty_points'] ?? 0);

        if (($data['pay_method'] ?? 'counter') === 'qris' && $this->midtrans->isConfigured()) {
            try {
                $itemIds = $order->items()->pluck('id')->all();
                $payment = $this->orderService->createQrisPayment(
                    $order,
                    $itemIds,
                    null,
                    $pointsToRedeem > 0 ? $pointsToRedeem : null
                );
                $charge = $this->midtrans->createQrisCharge($payment);
                $payment->update([
                    'midtrans_order_id' => $charge['midtrans_order_id'],
                    'midtrans_transaction_id' => $charge['transaction_id'],
                    'qris_url' => $charge['qris_url'],
                ]);
            } catch (\RuntimeException $e) {
                return redirect()->route('customer.thanks', [$slug, $order->order_number])
                    ->with('error', 'Pesanan dibuat, tapi QRIS gagal: ' . $e->getMessage());
            }

            return redirect()->route('customer.qris', [$slug, $order->order_number]);
        }

        if ($pointsToRedeem > 0) {
            return back()->with('error', 'Redeem poin hanya tersedia saat bayar QRIS langsung. Untuk bayar di kasir, minta kasir redeem saat pembayaran.');
        }

        return redirect()->route('customer.thanks', [$slug, $order->order_number]);
    }

    public function thanks(string $slug, string $orderNumber)
    {
        $outlet = Outlet::where('slug', $slug)->firstOrFail();
        $order = Order::where('outlet_id', $outlet->id)->where('order_number', $orderNumber)->first();

        return view('customer.thanks', [
            'outlet' => $outlet,
            'orderNumber' => $orderNumber,
            'order' => $order,
        ]);
    }
}
