<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReward extends Model
{
    protected $fillable = [
        'review_id',
        'coins',
        'points',
        'xp',
        'topics_awarded',
    ];

    protected function casts(): array
    {
        return [
            'coins' => 'integer',
            'points' => 'integer',
            'xp' => 'integer',
            'topics_awarded' => 'array',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
