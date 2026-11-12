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
        'cover_path',
        'valid',
        'ai_labels',
        'width',
        'height',
        'duration_seconds',
        'position',
        'removed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReviewMediaType::class,
            'valid' => 'boolean',
            'ai_labels' => 'array',
            'removed_at' => 'datetime',
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

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }
}
