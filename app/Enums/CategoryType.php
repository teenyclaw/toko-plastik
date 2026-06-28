<?php

namespace App\Enums;

enum CategoryType: string
{
    case Plastik = 'plastik';
    case BahanKue = 'bahan_kue';

    public function label(): string
    {
        return match ($this) {
            self::Plastik => 'Plastik',
            self::BahanKue => 'Bahan Kue',
        };
    }
}
