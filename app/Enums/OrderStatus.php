<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Open = 'open';
    case Cooking = 'cooking';
    case Ready = 'ready';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Antrian Dapur',
            self::Open => 'Bill Terbuka',
            self::Cooking => 'Sedang Dimasak',
            self::Ready => 'Siap Disajikan',
            self::Confirmed => 'Sudah Disajikan',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::Paid => 'Lunas',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Open => 'bg-purple-100 text-purple-800',
            self::Cooking => 'bg-orange-100 text-orange-800',
            self::Ready => 'bg-emerald-100 text-emerald-800',
            self::Confirmed => 'bg-blue-100 text-blue-800',
            self::Completed, self::Paid => 'bg-green-100 text-green-800',
            self::Cancelled => 'bg-red-100 text-red-800',
        };
    }

    public function isKitchenActive(): bool
    {
        return in_array($this, [self::Pending, self::Cooking, self::Ready], true);
    }

    /** @return array<int, self> */
    public static function kitchenStatuses(): array
    {
        return [self::Pending, self::Cooking, self::Ready];
    }
}
