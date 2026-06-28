<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Tempo = 'tempo';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Tempo => 'Tempo',
            self::Transfer => 'Transfer',
        };
    }
}
