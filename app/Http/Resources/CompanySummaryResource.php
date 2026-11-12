<?php

namespace App\Http\Resources;

use App\Models\Company;
use App\Support\PublicFileUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Company */
class CompanySummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logoUrl' => PublicFileUrl::of($this->logo_url),
            'coin' => $this->whenLoaded('coin', fn () => [
                'name' => $this->coin?->name,
                'icon' => $this->coin?->icon,
                'color' => $this->coin?->color,
            ]),
        ];
    }
}
