<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyCoin extends Model
{
    protected $fillable = [
        'company_id',
        'is_enabled',
        'name',
        'color',
        'icon',
        'usage_description',
        'coin_rate',
        'real_value_rate',
        'coupons_enabled',
        'webhook_url',
        'webhook_secret',
    ];

    protected $hidden = [
        'webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'coupons_enabled' => 'boolean',
            'coin_rate' => 'decimal:2',
            'real_value_rate' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
