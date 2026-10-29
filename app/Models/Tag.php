<?php

namespace App\Models;

use App\Enums\ReviewCharacteristic;
use App\Enums\TagSentiment;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'sentiment',
        'characteristic',
        'categories',
    ];

    protected function casts(): array
    {
        return [
            'sentiment' => TagSentiment::class,
            'characteristic' => ReviewCharacteristic::class,
            'categories' => 'array',
        ];
    }
}
