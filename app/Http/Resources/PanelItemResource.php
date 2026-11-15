<?php

namespace App\Http\Resources;

use App\Enums\ItemKind;
use App\Models\Item;
use App\Models\ItemCity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Item */
class PanelItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $average = $this->reviews_avg_average_rating;

        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'title' => $this->title,
            'description' => $this->description,
            'categories' => $this->categories,
            'brand' => $this->brand,
            'service' => $this->kind === ItemKind::Service ? [
                'durationMinutes' => $this->duration_minutes,
                'recurrence' => $this->recurrence,
                'modality' => $this->service_modality,
                'cep' => $this->cep,
                'cities' => $this->whenLoaded('cities', fn () => $this->cities->map(fn (ItemCity $city) => [
                    'ibge' => (int) $city->ibge_code,
                    'name' => $city->city,
                    'uf' => $city->state,
                ])),
            ] : null,
            'active' => $this->is_active,
            'coverUrl' => $this->cover?->url(),
            'media' => $this->whenLoaded('media', fn () => PanelItemMediaResource::collection($this->media)),
            'averageRating' => $average === null ? null : round((float) $average, 2),
            'reviewCount' => (int) ($this->reviews_count ?? 0),
        ];
    }
}
