<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Review extends Model
{
    protected $fillable = [
        'customer_id',
        'item_id',
        'delivery_rating',
        'quality_rating',
        'conformity_rating',
        'value_rating',
        'average_rating',
        'comment',
        'would_buy_again',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'would_buy_again' => 'boolean',
            'average_rating' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'review_tag');
    }
}
