<?php

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Item */
class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'companyId' => $this->company_id,
            'kind' => $this->kind,
            'categories' => $this->categories,
            'title' => $this->title,
            'brand' => $this->brand,
            'durationMinutes' => $this->duration_minutes,
            'serviceModality' => $this->service_modality,
            'recurrence' => $this->recurrence,
            'cep' => $this->cep,
            'description' => $this->description,
            'averageRating' => round((float) ($this->reviews_avg_average_rating ?? 0), 1),
            'reviewCount' => (int) ($this->reviews_count ?? 0),
        ];
    }
}
