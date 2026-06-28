<?php

namespace App\Models;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'outlet_id',
        'dining_table_id',
        'source',
        'created_by_user_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'notes',
        'status',
        'total',
        'payment_method',
        'amount_paid',
        'change_amount',
        'paid_at',
        'kitchen_started_at',
        'kitchen_ready_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'kitchen_started_at' => 'datetime',
        'kitchen_ready_at' => 'datetime',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class)->orderByDesc('paid_at');
    }

    public function unpaidItems()
    {
        return $this->items()->where('is_paid', false);
    }

    public function remainingBalance(): int
    {
        return (int) $this->unpaidItems()->get()->sum(fn (OrderItem $item) => $item->subtotal());
    }

    public function formattedRemainingBalance(): string
    {
        return 'Rp ' . number_format($this->remainingBalance(), 0, ',', '.');
    }

    public function hasUnpaidItems(): bool
    {
        return $this->unpaidItems()->exists();
    }

    public function statusEnum(): OrderStatus
    {
        return OrderStatus::from($this->status);
    }

    public function sourceEnum(): OrderSource
    {
        return OrderSource::from($this->source);
    }

    public function isActiveBill(): bool
    {
        return ! in_array($this->status, [
            OrderStatus::Paid->value,
            OrderStatus::Cancelled->value,
            OrderStatus::Completed->value,
        ], true);
    }

    public function formattedTotal(): string
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    public function displayCustomer(): string
    {
        if ($this->diningTable) {
            return $this->diningTable->name;
        }

        return $this->customer_name;
    }

    public function kitchenWaitMinutes(): int
    {
        $from = $this->kitchen_started_at ?? $this->created_at;

        return $from ? (int) $from->diffInMinutes(now()) : 0;
    }

    public function isKitchenActive(): bool
    {
        return $this->statusEnum()->isKitchenActive();
    }
}
