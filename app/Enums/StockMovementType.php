<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case Purchase = 'purchase';
    case Return = 'return';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Stok Masuk',
            self::Out => 'Stok Keluar',
            self::Adjustment => 'Penyesuaian',
            self::Sale => 'Penjualan',
            self::Purchase => 'Pembelian',
            self::Return => 'Retur',
        };
    }
}
