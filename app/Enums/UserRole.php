<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Kasir = 'kasir';
    case Gudang = 'gudang';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Kasir => 'Kasir',
            self::Gudang => 'Gudang',
        };
    }
}
