<?php

namespace App\Http\Controllers;

use App\Http\Resources\CoinBalanceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $client = $request->user()->client;

        return $this->sendResponse([
            'aprovPoints' => $client->points_balance,
            'coins' => CoinBalanceResource::collection(
                $client->coinBalances()->where('balance', '>', 0)->get()
            ),
        ]);
    }
}
