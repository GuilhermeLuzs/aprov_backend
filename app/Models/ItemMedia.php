<?php

namespace App\Models;

use App\Enums\ReviewMediaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ItemMedia extends Model
{
    protected $fillable = [
        'item_id',
        'type',
        'path',
        'cover_path',
        'width',
        'height',
        'duration_seconds',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReviewMediaType::class,
            'width' => 'integer',
            'height' => 'integer',
            'duration_seconds' => 'integer',
            'position' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
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
