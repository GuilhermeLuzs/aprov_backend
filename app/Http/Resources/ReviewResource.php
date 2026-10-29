<?php

namespace App\Http\Resources;

use App\Enums\TagSentiment;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Review */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tags = $this->whenLoaded('tags', fn () => $this->tags, collect());

        return [
            'id' => $this->id,
            'itemId' => $this->item_id,
            'customerId' => $this->customer_id,
            'deliveryRating' => $this->delivery_rating,
            'qualityRating' => $this->quality_rating,
            'conformityRating' => $this->conformity_rating,
            'valueRating' => $this->value_rating,
            'averageRating' => (float) $this->average_rating,
            'comment' => $this->comment,
            'wouldBuyAgain' => $this->would_buy_again,
            'mediaConsent' => $this->media_consent,
            'positiveTags' => TagResource::collection($tags->where('sentiment', TagSentiment::Positive)->values()),
            'negativeTags' => TagResource::collection($tags->where('sentiment', TagSentiment::Negative)->values()),
            'media' => ReviewMediaResource::collection($this->whenLoaded('media')),
            'rewards' => $this->whenLoaded('reward', fn () => [
                'coins' => $this->reward->coins,
                'aprovPoints' => $this->reward->points,
                'xp' => $this->reward->xp,
            ]),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
