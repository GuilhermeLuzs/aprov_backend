<?php

namespace App\Models;

use App\Enums\ReactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReviewComment extends Model
{
    use SoftDeletes;

    protected $fillable = ['review_id', 'user_id', 'parent_id', 'body', 'edited_at'];

    protected function casts(): array
    {
        return ['edited_at' => 'datetime'];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest()->orderBy('id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }

    public function scopeForViewer(Builder $query, ?User $viewer): void
    {
        $query->select('review_comments.*')->withReactionCounts();

        if (! $viewer) {
            return;
        }

        $query
            ->addSelect(['viewer_vote' => CommentReaction::query()
                ->select('type')
                ->whereColumn('review_comment_id', 'review_comments.id')
                ->where('user_id', $viewer->id)
                ->limit(1)])
            ->whereNotIn('review_comments.id', Report::query()
                ->select('reportable_id')
                ->where('reporter_id', $viewer->id)
                ->where('reportable_type', Report::TARGET_COMMENT));
    }

    public function scopeWithReactionCounts(Builder $query): void
    {
        $query->withCount([
            'reactions as agreements_count' => fn (Builder $q) => $q->where('type', ReactionType::Agree),
            'reactions as disagreements_count' => fn (Builder $q) => $q->where('type', ReactionType::Disagree),
        ]);
    }
}
