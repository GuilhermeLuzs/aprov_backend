<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'title', 'description', 'coin_cost', 'valid_until'];

    protected function casts(): array
    {
        return ['coin_cost' => 'integer', 'valid_until' => 'date'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function codes(): HasMany
    {
        return $this->hasMany(CouponCode::class);
    }

    public function scopeAvailable(Builder $query): void
    {
        $query->whereDate('valid_until', '>=', now(config('aprov.panel_timezone'))->toDateString())
            ->whereHas('codes', fn (Builder $codes) => $codes->whereNull('coupon_redemption_id'))
            ->whereHas('company.coin', fn (Builder $coin) => $coin->where('is_enabled', true));
    }
}
