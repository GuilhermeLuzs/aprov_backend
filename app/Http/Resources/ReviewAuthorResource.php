<?php

namespace App\Http\Resources;

use App\Models\Customer;
use App\Support\PublicFileUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class ReviewAuthorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user?->name,
            'username' => $this->username,
            'avatarUrl' => PublicFileUrl::of($this->user?->avatar_path),
            'level' => (int) $this->level,
        ];
    }
}
