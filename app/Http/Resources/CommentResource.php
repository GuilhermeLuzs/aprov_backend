<?php

namespace App\Http\Resources;

use App\Models\ReviewComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReviewComment */
class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $deleted = $this->trashed();

        return [
            'id' => $this->id,
            'reviewId' => $this->review_id,
            'parentId' => $this->parent_id,
            'author' => $this->whenLoaded('user', fn () => $this->user?->customer
                ? new ReviewAuthorResource($this->user->customer->setRelation('user', $this->user))
                : null),
            'body' => $deleted ? '' : $this->body,
            'deleted' => $deleted,
            'agreements' => (int) ($this->agreements_count ?? 0),
            'disagreements' => (int) ($this->disagreements_count ?? 0),
            'myVote' => $this->whenHas('viewer_vote'),
            'createdAt' => $this->created_at->toIso8601String(),
            'editedAt' => $this->edited_at?->toIso8601String(),
            'replies' => self::collection($this->whenLoaded('replies')),
        ];
    }
}
