<?php

namespace App\Models;

use App\Enums\ItemKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'kind',
        'categories',
        'title',
        'brand',
        'duration_minutes',
        'service_modality',
        'recurrence',
        'cep',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'kind' => ItemKind::class,
            'categories' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
