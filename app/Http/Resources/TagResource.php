<?php

namespace App\Http\Resources;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tag */
class TagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->name,
            'sentiment' => $this->sentiment,
            'characteristic' => $this->characteristic,
            'kind' => $this->kind,
            'category' => $this->categories[0] ?? null,
            'oppositeId' => $this->opposite_id,
            'general' => $this->kind === null && empty($this->categories),
        ];
    }
}
