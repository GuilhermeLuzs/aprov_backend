<?php

namespace App\Models;

use App\Enums\CollectionsVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'username',
        'cpf',
        'birth_date',
        'bio',
        'collections_visibility',
        'preferred_categories',
        'discovery_source',
    ];

    protected $hidden = [
        'cpf',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'collections_visibility' => CollectionsVisibility::class,
            'preferred_categories' => 'array',
            'points_balance' => 'integer',
            'xp' => 'integer',
            'level' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coinBalances(): HasMany
    {
        return $this->hasMany(CustomerCoinBalance::class);
    }

    public function followedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withTimestamps();
    }
}
