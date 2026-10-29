<?php

namespace App\Models;

use App\Enums\CompanyModality;
use App\Enums\CompanySize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Company extends Model
{
    protected $fillable = [
        'cnpj',
        'name',
        'email',
        'phone',
        'zip_code',
        'street',
        'number',
        'complement',
        'district',
        'city',
        'state',
        'responsible_name',
        'responsible_cpf',
        'responsible_phone',
        'niche',
        'modality',
        'size',
    ];

    protected $hidden = [
        'responsible_cpf',
    ];

    protected function casts(): array
    {
        return [
            'modality' => CompanyModality::class,
            'size' => CompanySize::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
