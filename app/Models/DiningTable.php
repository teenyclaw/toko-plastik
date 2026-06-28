<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DiningTable extends Model
{
    protected $fillable = [
        'outlet_id',
        'name',
        'token',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (DiningTable $table) {
            if (empty($table->token)) {
                $table->token = Str::lower(Str::random(8));
            }
        });
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function activeBill(): ?Order
    {
        return $this->orders()
            ->whereNotIn('status', [
                OrderStatus::Paid->value,
                OrderStatus::Cancelled->value,
                OrderStatus::Completed->value,
            ])
            ->latest()
            ->first();
    }

    public function isOccupied(): bool
    {
        return $this->activeBill() !== null;
    }

    public function qrUrl(): string
    {
        return url('/o/' . $this->outlet->slug . '/t/' . $this->token);
    }
}
