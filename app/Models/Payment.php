<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'type',
        'amount',
        'method',
        'date',
        'notes',
        'customer_id',
        'supplier_id',
        'sale_id',
        'purchase_id',
        'created_at',
    ];

    protected $casts = [
        'type' => PaymentType::class,
        'amount' => 'decimal:2',
        'method' => PaymentMethod::class,
        'date' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function partyName(): string
    {
        return $this->customer?->name
            ?? $this->supplier?->name
            ?? '—';
    }
}
