<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModifierGroup extends Model
{
    protected $fillable = [
        'menu_item_id',
        'name',
        'selection_type',
        'min_select',
        'max_select',
        'sort_order',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ModifierOption::class)->orderBy('sort_order');
    }

    public function isSingle(): bool
    {
        return $this->selection_type === 'single';
    }

    public function isMultiple(): bool
    {
        return $this->selection_type === 'multiple';
    }
}
