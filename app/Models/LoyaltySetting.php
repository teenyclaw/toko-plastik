<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltySetting extends Model
{
    protected $fillable = [
        'outlet_id',
        'is_enabled',
        'earn_amount_basis',
        'earn_points',
        'redeem_rp_per_point',
        'min_redeem_points',
        'max_redeem_percent',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public static function defaultsFor(int $outletId): array
    {
        return [
            'outlet_id' => $outletId,
            'is_enabled' => true,
            'earn_amount_basis' => 1000,
            'earn_points' => 1,
            'redeem_rp_per_point' => 100,
            'min_redeem_points' => 50,
            'max_redeem_percent' => 50,
        ];
    }
}
