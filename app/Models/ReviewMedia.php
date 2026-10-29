<?php

namespace App\Models;

use App\Enums\ReviewMediaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReviewMedia extends Model
{
    protected $fillable = [
        'review_id',
        'type',
        'path',
        'width',
        'height',
        'duration_seconds',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReviewMediaType::class,
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
