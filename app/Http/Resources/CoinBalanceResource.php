<?php

namespace App\Http\Resources;

use App\Models\CustomerCoinBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CustomerCoinBalance */
class CoinBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'companyId' => $this->company_id,
            'amount' => $this->balance,
        ];
    }
}
