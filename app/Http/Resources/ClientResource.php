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
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
