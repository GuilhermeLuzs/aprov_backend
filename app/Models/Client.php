<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    protected $fillable = [
        'username',
        'cpf',
        'birth_date',
        'preferred_categories',
        'discovery_source',
        'followed_company_ids',
    ];

    protected $hidden = [
        'cpf',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'preferred_categories' => 'array',
            'followed_company_ids' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
