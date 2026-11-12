<?php

namespace App\Models;

use App\Enums\CompanyModality;
use App\Enums\CompanySize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    protected $fillable = [
        'cnpj',
        'name',
        'logo_url',
        'description',
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

    public function coin(): HasOne
    {
        return $this->hasOne(CompanyCoin::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(Review::class, Item::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class)->withTimestamps();
    }

    public function coinRuleTopics(): HasMany
    {
        return $this->hasMany(CompanyCoinRuleTopic::class);
    }
}
