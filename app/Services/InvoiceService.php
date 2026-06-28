<?php

namespace App\Services;

class InvoiceService
{
    public static function generate(string $prefix = 'INV'): string
    {
        $date = now();
        $rand = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix.$date->format('ymd').$rand;
    }
}
