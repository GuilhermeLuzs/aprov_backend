<?php

namespace App\Models;

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
        return $this->hasMany(ReviewMedia::class)->orderBy('position');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ReviewVersion::class)->orderByDesc('published_at');
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
