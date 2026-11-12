<?php

namespace App\Http\Resources;

use App\Models\ItemMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ItemMedia */
class ItemMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'url' => $this->url(),
            'coverUrl' => $this->coverUrl(),
            'width' => $this->width,
            'height' => $this->height,
            'durationSeconds' => $this->duration_seconds,
        ];
    }
}
