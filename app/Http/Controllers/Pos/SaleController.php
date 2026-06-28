<?php

namespace App\Http\Controllers\Pos;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private SaleService $saleService)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,tempo'],
            'paid' => ['nullable', 'numeric', 'min:0'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $paymentMethod = PaymentMethod::from($data['payment_method']);

        $sale = $this->saleService->checkout(
            user: $request->user(),
            items: $data['items'],
            paymentMethod: $paymentMethod,
            paid: (float) ($data['paid'] ?? 0),
            customerId: $data['customer_id'] ?? null,
            discount: (float) ($data['discount'] ?? 0),
            tax: (float) ($data['tax'] ?? 0),
            notes: $data['notes'] ?? null,
        );

        return redirect()
            ->route('pos.receipt', $sale)
            ->with('success', 'Transaksi berhasil.');
    }
}
