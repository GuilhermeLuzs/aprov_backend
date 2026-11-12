<?php

namespace App\Http\Resources;

use App\Enums\ReviewMediaType;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Item */
class ItemCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'companyId' => $this->company_id,
            'kind' => $this->kind,
            'categories' => $this->categories,
            'title' => $this->title,
            'coverUrl' => $this->whenLoaded('media', fn () => $this->media->firstWhere('type', ReviewMediaType::Photo)?->url()),
            'averageRating' => round((float) ($this->reviews_avg_average_rating ?? 0), 1),
            'reviewCount' => (int) ($this->reviews_count ?? 0),
            'maxRewards' => $this->whenLoaded('company', fn () => $this->resource->maxRewards()),
            'saved' => $this->whenHas('saved_by_viewer', fn ($saved) => (bool) $saved),
            'purchased' => $this->whenHas('purchased_by_viewer', fn ($purchased) => (bool) $purchased),
        ];
    }
}
