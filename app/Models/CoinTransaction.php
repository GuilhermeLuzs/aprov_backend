<?php

namespace App\Models;

use App\Enums\CoinRuleTopic;
use App\Enums\CoinTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinTransaction extends Model
{
    protected $fillable = [
        'customer_id',
        'company_id',
        'type',
        'amount',
        'review_id',
        'topic',
        'coupon_redemption_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => CoinTransactionType::class,
            'topic' => CoinRuleTopic::class,
            'amount' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
