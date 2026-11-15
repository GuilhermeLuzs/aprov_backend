<?php

namespace App\Http\Controllers;

use App\Enums\CoinTransactionType;
use App\Http\Resources\CompanySummaryResource;
use App\Models\CoinTransaction;
use App\Models\Company;
use App\Models\Coupon;
use App\Models\CouponCode;
use App\Models\CouponRedemption;
use App\Models\CustomerCoinBalance;
use App\Support\FullTextSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as Status;

class CouponController extends Controller
{
    private const COMPANIES_PER_PAGE = 10;

    private const PREVIEW_COUPONS = 3;

    private const REDEMPTIONS_PER_PAGE = 20;

    public function companies(Request $request): JsonResponse
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']]);
        $text = FullTextSearch::of(trim((string) $request->query('search', '')));

        $page = Company::query()
            ->whereHas('coupons', fn (Builder $coupons) => $coupons->available())
            ->when(! $text->isEmpty(), fn (Builder $q) => $text->constrain($q, 'name'))
            ->with(['coin', 'coupons' => fn ($coupons) => $coupons->available()->orderBy('coin_cost')])
            ->orderByDesc(CustomerCoinBalance::query()
                ->select('balance')
                ->whereColumn('company_id', 'companies.id')
                ->where('customer_id', $this->viewerCustomer($request)->id))
            ->orderBy('name')
            ->paginate(self::COMPANIES_PER_PAGE);

        return $this->sendPage($page, 'companies', $page->getCollection()->map(fn (Company $company) => [
            'company' => new CompanySummaryResource($company),
            'couponCount' => $company->coupons->count(),
            'coupons' => $company->coupons->take(self::PREVIEW_COUPONS)->map(fn (Coupon $coupon) => $this->coupon($coupon))->values(),
        ]));
    }

    public function forCompany(Company $company): JsonResponse
    {
        $coupons = $company->coupons()->available()->orderBy('coin_cost')->get();

        return $this->sendResponse($coupons->map(fn (Coupon $coupon) => $this->coupon($coupon))->values());
    }

    public function mine(Request $request): JsonResponse
    {
        $page = CouponRedemption::query()
            ->where('customer_id', $this->viewerCustomer($request)->id)
            ->with(['coupon', 'company.coin'])
            ->latest('id')
            ->paginate(self::REDEMPTIONS_PER_PAGE);

        return $this->sendPage($page, 'redemptions', $page->getCollection()->map(fn (CouponRedemption $redemption) => $this->redemption($redemption)));
    }

    public function redeem(Request $request, Coupon $coupon): JsonResponse
    {
        $key = (string) $request->header('Idempotency-Key');

        if (! Str::isUuid($key)) {
            return $this->sendError('Pedido inválido. Tente de novo.', [], Status::HTTP_BAD_REQUEST);
        }

        $customer = $this->viewerCustomer($request);

        try {
            $result = $this->redeemOnce($coupon, $customer->id, $key);
        } catch (UniqueConstraintViolationException) {
            $result = CouponRedemption::query()->where('customer_id', $customer->id)->where('idempotency_key', $key)->firstOrFail();
        }

        if (is_string($result)) {
            return $this->sendError($result, [], Status::HTTP_CONFLICT);
        }

        $balance = CustomerCoinBalance::query()->where('customer_id', $customer->id)->where('company_id', $coupon->company_id)->value('balance');

        return $this->sendResponse(
            ['redemption' => $this->redemption($result->load(['coupon', 'company.coin'])), 'balance' => (int) $balance],
            'Cupom resgatado.',
            Status::HTTP_CREATED,
        );
    }

    private function redeemOnce(Coupon $coupon, int $customerId, string $key): CouponRedemption|string
    {
        return DB::transaction(function () use ($coupon, $customerId, $key) {
            $repeated = CouponRedemption::query()->where('customer_id', $customerId)->where('idempotency_key', $key)->first();

            if ($repeated) {
                return $repeated;
            }

            if (! Coupon::query()->whereKey($coupon->id)->available()->exists()) {
                return 'Este cupom não está mais disponível.';
            }

            $balance = CustomerCoinBalance::query()
                ->where('customer_id', $customerId)
                ->where('company_id', $coupon->company_id)
                ->lockForUpdate()
                ->first();

            if (! $balance || $balance->balance < $coupon->coin_cost) {
                return 'Você não tem moedas suficientes para este cupom.';
            }

            $code = CouponCode::query()->where('coupon_id', $coupon->id)->whereNull('coupon_redemption_id')->orderBy('id')->lockForUpdate()->first();

            if (! $code) {
                return 'Os códigos deste cupom acabaram.';
            }

            $redemption = CouponRedemption::create([
                'customer_id' => $customerId,
                'company_id' => $coupon->company_id,
                'coupon_id' => $coupon->id,
                'code' => $code->code,
                'coins_spent' => $coupon->coin_cost,
                'idempotency_key' => $key,
            ]);
            $code->update(['coupon_redemption_id' => $redemption->id, 'delivered_at' => now()]);
            $balance->decrement('balance', $coupon->coin_cost);
            CoinTransaction::create([
                'customer_id' => $customerId,
                'company_id' => $coupon->company_id,
                'type' => CoinTransactionType::Spent,
                'amount' => $coupon->coin_cost,
                'coupon_redemption_id' => $redemption->id,
            ]);

            return $redemption;
        });
    }

    private function coupon(Coupon $coupon): array
    {
        return [
            'id' => $coupon->id,
            'companyId' => $coupon->company_id,
            'title' => $coupon->title,
            'description' => $coupon->description,
            'coinCost' => $coupon->coin_cost,
            'validUntil' => $coupon->valid_until->toDateString(),
        ];
    }

    private function redemption(CouponRedemption $redemption): array
    {
        return [
            'id' => $redemption->id,
            'couponId' => $redemption->coupon_id,
            'title' => $redemption->coupon?->title,
            'company' => new CompanySummaryResource($redemption->company),
            'code' => $redemption->code,
            'coinsSpent' => $redemption->coins_spent,
            'redeemedAt' => $redemption->created_at?->toIso8601String(),
        ];
    }
}
