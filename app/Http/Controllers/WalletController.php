<?php

namespace App\Http\Controllers;

use App\Http\Resources\CoinBalanceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        return $this->sendResponse([
            'aprovPoints' => $customer->points_balance,
            'coins' => CoinBalanceResource::collection(
                $customer->coinBalances()->where('balance', '>', 0)->get()
            ),
        ]);
    }
}
