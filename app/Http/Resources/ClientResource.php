<?php

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Client */
class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user->name,
            'username' => $this->username,
            'email' => $this->user->email,
            'birthDate' => $this->birth_date->toDateString(),
            'preferredCategories' => $this->preferred_categories,
            'discoverySource' => $this->discovery_source,
            'followedCompanyIds' => $this->followed_company_ids,
            'pointsBalance' => $this->points_balance,
            'xp' => $this->xp,
            'level' => $this->level,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
