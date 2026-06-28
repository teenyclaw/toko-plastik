<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class MenuItem extends Model
{
    protected $fillable = [
        'outlet_id',
        'category_id',
        'name',
        'description',
        'price',
        'photo',
        'is_available',
        'track_stock',
        'stock_qty',
        'low_stock_threshold',
        'sort_order',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'track_stock' => 'boolean',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function modifierGroups(): HasMany
    {
        return $this->hasMany(ModifierGroup::class)->orderBy('sort_order');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    public function stockLabel(): ?string
    {
        if (! $this->track_stock) {
            return null;
        }

        return $this->stock_qty . ' pcs';
    }

    public function isLowStock(): bool
    {
        return $this->track_stock && $this->stock_qty <= $this->low_stock_threshold;
    }

    public function hasModifiers(): bool
    {
        if ($this->relationLoaded('modifierGroups')) {
            return $this->modifierGroups->isNotEmpty();
        }

        return $this->modifierGroups()->exists();
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? Storage::url($this->photo) : null;
    }

    public function formattedPrice(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }
}
