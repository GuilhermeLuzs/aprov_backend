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
            'id' => $this->mock_id,
            'companyId' => $this->company_id,
            'kind' => $this->kind,
            'categories' => $this->categories,
            'title' => $this->title,
            'description' => $this->description,
            'coinsReward' => $this->coins_reward,
            'xpReward' => $this->xp_reward,
            'averageRating' => round((float) ($this->reviews_avg_average_rating ?? 0), 1),
            'reviewCount' => (int) ($this->reviews_count ?? 0),
        ];
    }
}
