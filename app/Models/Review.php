<?php

namespace App\Models;

use App\Enums\ReactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use SoftDeletes;

    public const STATUS_PUBLISHED = 'published';

    public const CARD_RELATIONS = ['tags', 'media', 'customer.user', 'item.media', 'item.company.coin'];

    protected $fillable = [
        'customer_id',
        'item_id',
        'delivery_rating',
        'quality_rating',
        'conformity_rating',
        'value_rating',
        'average_rating',
        'comment',
        'would_buy_again',
        'media_consent',
        'status',
        'moderated_by',
        'edited_at',
        'author_removed_at',
    ];

    protected function casts(): array
    {
        return [
            'item_id' => 'integer',
            'delivery_rating' => 'integer',
            'quality_rating' => 'integer',
            'conformity_rating' => 'integer',
            'value_rating' => 'integer',
            'would_buy_again' => 'boolean',
            'media_consent' => 'boolean',
            'average_rating' => 'decimal:2',
            'edited_at' => 'datetime',
            'author_removed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'review_tag');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ReviewMedia::class)->whereNull('removed_at')->orderBy('position');
    }

    public function allMedia(): HasMany
    {
        return $this->hasMany(ReviewMedia::class);
    }

    public function loadHistory(): self
    {
        $this->load(['versions', 'allMedia']);

        $tags = Tag::query()
            ->whereIn('id', $this->versions->pluck('tag_ids')->flatten()->unique()->all())
            ->get()
            ->keyBy('id');

        foreach ($this->versions as $version) {
            $version->setRelation('tags', collect($version->tag_ids ?? [])->map(fn ($id) => $tags->get($id))->filter()->values());
            $version->setRelation('media', collect($version->media_ids ?? [])->map(fn ($id) => $this->allMedia->firstWhere('id', $id))->filter()->values());
        }

        return $this;
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ReviewVersion::class)->orderByDesc('published_at');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ReviewReaction::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReviewComment::class);
    }

    public function scopeWithEngagement(Builder $query): void
    {
        $query->withCount([
            'reactions as agreements_count' => fn (Builder $q) => $q->where('type', ReactionType::Agree),
            'reactions as disagreements_count' => fn (Builder $q) => $q->where('type', ReactionType::Disagree),
            'comments as comments_count',
        ]);
    }

    public function scopeForViewer(Builder $query, ?User $viewer): void
    {
        $query->select('reviews.*')->withEngagement();

        if (! $viewer) {
            return;
        }

        $query
            ->addSelect(['viewer_vote' => ReviewReaction::query()
                ->select('type')
                ->whereColumn('review_id', 'reviews.id')
                ->where('user_id', $viewer->id)
                ->limit(1)])
            ->whereNotIn('reviews.id', Report::query()
                ->select('reportable_id')
                ->where('reporter_id', $viewer->id)
                ->where('reportable_type', Report::TARGET_REVIEW));
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeWithAuthor(Builder $query): void
    {
        $query->whereNull('author_removed_at');
    }

    public static function customerReviewedBefore(int $customerId, int $itemId, ?int $exceptReviewId = null): bool
    {
        return static::withTrashed()
            ->where('customer_id', $customerId)
            ->where('item_id', $itemId)
            ->when($exceptReviewId, fn (Builder $query) => $query->whereKeyNot($exceptReviewId))
            ->exists();
    }

    public function hasAuthor(): bool
    {
        return $this->author_removed_at === null;
    }

    public function reward(): HasOne
    {
        return $this->hasOne(ReviewReward::class);
    }
}
