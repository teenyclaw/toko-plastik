<?php

namespace App\Enums;

enum OrderSource: string
{
    case Customer = 'customer';
    case Table = 'table';
    case Waiter = 'waiter';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'QR Outlet',
            self::Table => 'QR Meja',
            self::Waiter => 'Waiter / Kasir',
        };
    }
}
