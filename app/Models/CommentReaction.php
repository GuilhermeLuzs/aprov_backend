<?php

namespace App\Models;

use App\Enums\ReactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentReaction extends Model
{
    protected $fillable = ['user_id', 'review_comment_id', 'type'];

    protected function casts(): array
    {
        return ['type' => ReactionType::class];
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(ReviewComment::class, 'review_comment_id');
    }
}
