<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'order_id',
        'cashier_shift_id',
        'status',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'qris_url',
        'expires_at',
        'amount',
        'loyalty_points_redeemed',
        'loyalty_discount',
        'payment_method',
        'amount_paid',
        'change_amount',
        'item_ids',
        'paid_at',
    ];

    protected $casts = [
        'item_ids' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function cashierShift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function formattedAmount(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }
}
