<?php

namespace App\Http\Resources;

use App\Models\ReviewVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReviewVersion */
class ReviewVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deliveryRating' => $this->delivery_rating,
            'qualityRating' => $this->quality_rating,
            'conformityRating' => $this->conformity_rating,
            'valueRating' => $this->value_rating,
            'averageRating' => (float) $this->average_rating,
            'comment' => $this->comment,
            'wouldBuyAgain' => $this->would_buy_again,
            'tagIds' => $this->tag_ids,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'media' => ReviewMediaResource::collection($this->whenLoaded('media')),
            'publishedAt' => $this->published_at->toIso8601String(),
        ];
    }
}
