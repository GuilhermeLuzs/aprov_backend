<?php

namespace App\Models;

use App\Enums\ItemKind;
use App\Enums\ItemState;
use App\Enums\ReviewMediaType;
use App\Enums\ServiceModality;
use App\Enums\ServiceRecurrence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

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
            'service_modality' => ServiceModality::class,
            'recurrence' => ServiceRecurrence::class,
            'duration_minutes' => 'integer',
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

    public function cover(): HasOne
    {
        return $this->hasOne(ItemMedia::class)->ofMany(['position' => 'min'], fn (Builder $query) => $query->where('type', ReviewMediaType::Photo));
    }

    public function cities(): HasMany
    {
        return $this->hasMany(ItemCity::class)->orderBy('state')->orderBy('city');
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

    public function rewardTopics(): Collection
    {
        $coinsEnabled = (bool) $this->company?->coin?->is_enabled;

        return ($this->company?->coinRuleTopics ?? collect())
            ->filter(fn ($topic) => $topic->kind === $this->kind && $topic->topic->isPayable())
            ->map(fn ($topic) => $coinsEnabled ? $topic : tap(clone $topic, fn ($withoutCoins) => $withoutCoins->coins = 0))
            ->values();
    }

    public function maxRewards(): array
    {
        $topics = $this->rewardTopics();

        return [
            'coins' => (int) $topics->sum('coins'),
            'aprovPoints' => (int) $topics->sum('points'),
            'xp' => (int) $topics->sum('xp'),
        ];
    }
}
