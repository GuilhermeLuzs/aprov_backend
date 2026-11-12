<?php

namespace App\Http\Resources;

use App\Enums\ReviewMediaType;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Item */
class ReviewItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'companyId' => $this->company_id,
            'kind' => $this->kind,
            'title' => $this->title,
            'coverUrl' => $this->whenLoaded('media', fn () => $this->media->firstWhere('type', ReviewMediaType::Photo)?->url()),
            'company' => $this->whenLoaded('company', fn () => new CompanySummaryResource($this->company)),
        ];
    }
}
