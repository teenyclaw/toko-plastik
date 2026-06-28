<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'menu_item_id',
        'item_name',
        'qty',
        'price',
        'note',
        'modifiers',
        'is_paid',
        'paid_at',
    ];

    protected $casts = [
        'modifiers' => 'array',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function subtotal(): int
    {
        return $this->qty * $this->price;
    }

    public function formattedSubtotal(): string
    {
        return 'Rp ' . number_format($this->subtotal(), 0, ',', '.');
    }

    /** @return array<int, array{group:string,option:string,price:int}> */
    public function modifierList(): array
    {
        return $this->modifiers ?? [];
    }

    public function modifierSummary(): ?string
    {
        $mods = $this->modifierList();

        if (empty($mods)) {
            return null;
        }

        return implode(', ', array_map(fn (array $m) => $m['option'], $mods));
    }
}
