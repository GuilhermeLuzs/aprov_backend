<?php

namespace App\Models;

use App\Enums\CoinRuleTopic;
use App\Enums\ItemKind;
use App\Enums\ItemState;
use Illuminate\Database\Eloquent\Builder;
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

    public function media(): HasMany
    {
        return $this->hasMany(ItemMedia::class)->orderBy('position');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function states(): HasMany
    {
        return $this->hasMany(CustomerItemState::class);
    }

    public function scopeForViewer(Builder $query, ?Customer $viewer): void
    {
        if (! $viewer) {
            return;
        }

        $query->withExists([
            'states as saved_by_viewer' => fn (Builder $q) => $q->where('customer_id', $viewer->id)->where('type', ItemState::Saved),
            'states as purchased_by_viewer' => fn (Builder $q) => $q->where('customer_id', $viewer->id)->where('type', ItemState::Purchased),
        ]);
    }

    public function maxRewards(): array
    {
        $topics = ($this->company?->coinRuleTopics ?? collect())
            ->reject(fn ($topic) => $this->kind === ItemKind::Service && $topic->topic === CoinRuleTopic::SentVideo);

        return [
            'coins' => (int) $topics->sum('coins'),
            'aprovPoints' => (int) $topics->sum('points'),
            'xp' => (int) $topics->sum('xp'),
        ];
    }
}
