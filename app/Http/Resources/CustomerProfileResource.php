<?php

namespace App\Http\Resources;

use App\Models\Customer;
use App\Support\PublicFileUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class CustomerProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user?->name,
            'username' => $this->username,
            'avatarUrl' => PublicFileUrl::of($this->user?->avatar_path),
            'bio' => $this->bio ?? '',
            'level' => (int) $this->level,
            'xp' => (int) $this->xp,
            'reviewCount' => $this->whenHas('reviews_count', fn ($count) => (int) $count),
            'agreementsReceived' => $this->whenHas('agreements_received', fn ($count) => (int) $count),
            'followerCount' => $this->whenHas('followers_count', fn ($count) => (int) $count),
            'followingCount' => $this->whenHas('following_count', fn ($count) => (int) $count),
            'isFollowing' => $this->whenHas('followed_by_viewer', fn ($following) => (bool) $following),
            'joinedAt' => $this->created_at->toIso8601String(),
        ];
    }
}
