<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewVersion extends Model
{
    protected $fillable = [
        'review_id',
        'delivery_rating',
        'quality_rating',
        'conformity_rating',
        'value_rating',
        'average_rating',
        'comment',
        'would_buy_again',
        'tag_ids',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'delivery_rating' => 'integer',
            'quality_rating' => 'integer',
            'conformity_rating' => 'integer',
            'value_rating' => 'integer',
            'average_rating' => 'decimal:2',
            'would_buy_again' => 'boolean',
            'tag_ids' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
