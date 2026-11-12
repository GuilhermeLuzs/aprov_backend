<?php

namespace App\Http\Resources;

use App\Models\Company;
use App\Support\PublicFileUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Company */
class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items', fn () => $this->items, collect());

        return [
            'id' => $this->id,
            'name' => $this->name,
            'logoUrl' => PublicFileUrl::of($this->logo_url),
            'description' => $this->description,
            'categories' => $items->pluck('categories')->flatten()->unique()->values(),
            'coin' => $this->whenLoaded('coin', fn () => [
                'name' => $this->coin?->name,
                'icon' => $this->coin?->icon,
                'color' => $this->coin?->color,
            ]),
            'itemCount' => $items->count(),
            'reviewCount' => (int) ($this->reviews_count ?? 0),
            'averageRating' => round((float) ($this->reviews_avg_average_rating ?? 0), 1),
            'followerCount' => (int) ($this->followers_count ?? 0),
        ];
    }
}
