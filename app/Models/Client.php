<?php

namespace App\Models;

use App\Enums\CollectionsVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    protected $fillable = [
        'username',
        'cpf',
        'birth_date',
        'bio',
        'collections_visibility',
    ];

    protected $hidden = [
        'cpf',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'collections_visibility' => CollectionsVisibility::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
