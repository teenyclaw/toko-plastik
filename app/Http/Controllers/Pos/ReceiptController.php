<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\StoreSetting;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function show(Sale $sale): View
    {
        $sale->load(['details.product', 'details.unit', 'customer', 'user']);

        return view('pos.receipt', [
            'sale' => $sale,
            'storeName' => StoreSetting::get('store_name', config('app.name')),
            'storeAddress' => StoreSetting::get('store_address', ''),
            'receiptFooter' => StoreSetting::get('receipt_footer', 'Terima kasih!'),
        ]);
    }
}
