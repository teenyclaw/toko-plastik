<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModifierOption extends Model
{
    protected $fillable = [
        'modifier_group_id',
        'name',
        'price_adjustment',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }

    public function formattedAdjustment(): string
    {
        if ($this->price_adjustment === 0) {
            return '';
        }

        $prefix = $this->price_adjustment > 0 ? '+' : '';

        return $prefix . 'Rp ' . number_format(abs($this->price_adjustment), 0, ',', '.');
    }
}
