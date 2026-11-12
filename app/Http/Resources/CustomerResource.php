<?php

namespace App\Http\Resources;

use App\Models\Customer;
use App\Support\PublicFileUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user->name,
            'username' => $this->username,
            'avatarUrl' => PublicFileUrl::of($this->user->avatar_path),
            'bio' => $this->bio ?? '',
            'email' => $this->user->email,
            'birthDate' => $this->birth_date->toDateString(),
            'preferredCategories' => $this->preferred_categories,
            'discoverySource' => $this->discovery_source,
            'followedCompanyIds' => $this->followedCompanies->pluck('id')->values(),
            'pointsBalance' => $this->points_balance,
            'xp' => $this->xp,
            'level' => $this->level,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
