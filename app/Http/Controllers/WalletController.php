<?php

namespace App\Http\Controllers;

use App\Http\Resources\CoinBalanceResource;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        $balances = $customer->coinBalances()->where('balance', '>', 0)->with('company.coin')->orderByDesc('balance')->get();
        $couponCounts = Coupon::query()
            ->available()
            ->whereIn('company_id', $balances->pluck('company_id'))
            ->selectRaw('company_id, COUNT(*) as total')
            ->groupBy('company_id')
            ->pluck('total', 'company_id');
        $balances->each(fn ($balance) => $balance->setAttribute('available_coupons_count', (int) ($couponCounts[$balance->company_id] ?? 0)));

        return $this->sendResponse([
            'aprovPoints' => $customer->points_balance,
            'xp' => (int) $customer->xp,
            'level' => (int) $customer->level,
            'coins' => CoinBalanceResource::collection($balances),
        ]);
    }
}
