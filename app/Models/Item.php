<?php

namespace App\Models;

use App\Enums\ItemKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = [
        'company_id',
        'kind',
        'categories',
        'title',
        'description',
        'coins_reward',
        'xp_reward',
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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
