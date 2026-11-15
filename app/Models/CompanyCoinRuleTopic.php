<?php

namespace App\Models;

use App\Enums\CoinRuleTopic;
use App\Enums\ItemKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyCoinRuleTopic extends Model
{
    protected $fillable = [
        'company_id',
        'kind',
        'topic',
        'threshold',
        'coins',
        'points',
        'xp',
    ];

    protected function casts(): array
    {
        return [
            'kind' => ItemKind::class,
            'topic' => CoinRuleTopic::class,
            'threshold' => 'integer',
            'coins' => 'integer',
            'points' => 'integer',
            'xp' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
