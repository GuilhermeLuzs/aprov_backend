<?php

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;

/** @mixin Item */
class ItemDetailResource extends ItemResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'company' => new CompanySummaryResource($this->company),
        ];
    }
}
