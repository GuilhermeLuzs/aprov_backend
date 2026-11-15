<?php

namespace Database\Seeders;

use App\Enums\CoinTransactionType;
use App\Models\CoinTransaction;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\CustomerCoinBalance;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CouponSeeder extends Seeder
{
    private const RANDOM_SEED = 20261116;

    private const CODES_PER_COUPON = 40;

    private const MAX_REDEMPTIONS_PER_COUPON = 8;

    private const HISTORY_DAYS = 60;

    public function run(): void
    {
        if (Coupon::query()->withTrashed()->exists()) {
            return;
        }

        mt_srand(self::RANDOM_SEED);
        $now = CarbonImmutable::now();

        DB::transaction(function () use ($now) {
            foreach (require database_path('data/coupons_seed.php') as $seed) {
                $coupon = Coupon::create([
                    'company_id' => $seed['company_id'],
                    'title' => $seed['title'],
                    'description' => $seed['description'],
                    'coin_cost' => $seed['coin_cost'],
                    'valid_until' => $seed['valid_until'],
                ]);

                $codes = [];
                while (count($codes) < self::CODES_PER_COUPON) {
                    $codes[$seed['code_prefix'].'-'.str_pad((string) mt_rand(0, 99999), 5, '0', STR_PAD_LEFT)] = true;
                }
                $coupon->codes()->createMany(array_map(fn (string $code) => ['code' => $code], array_keys($codes)));

                $this->seedRedemptions($coupon, $now);
            }
        });
    }

    private function seedRedemptions(Coupon $coupon, CarbonImmutable $now): void
    {
        $balances = CustomerCoinBalance::query()
            ->where('company_id', $coupon->company_id)
            ->where('balance', '>=', $coupon->coin_cost)
            ->orderBy('id')
            ->limit(mt_rand(0, self::MAX_REDEMPTIONS_PER_COUPON))
            ->get();

        foreach ($balances as $balance) {
            $code = $coupon->codes()->whereNull('coupon_redemption_id')->orderBy('id')->first();
            $moment = $now->subDays(mt_rand(1, self::HISTORY_DAYS))->subMinutes(mt_rand(0, 1439));
            $stamps = ['created_at' => $moment, 'updated_at' => $moment];

            $redemption = (new CouponRedemption([
                'customer_id' => $balance->customer_id,
                'company_id' => $coupon->company_id,
                'coupon_id' => $coupon->id,
                'code' => $code->code,
                'coins_spent' => $coupon->coin_cost,
                'idempotency_key' => (string) Str::uuid(),
            ]))->forceFill($stamps);
            $redemption->save();

            $code->update(['coupon_redemption_id' => $redemption->id, 'delivered_at' => $moment]);
            $balance->decrement('balance', $coupon->coin_cost);
            (new CoinTransaction([
                'customer_id' => $balance->customer_id,
                'company_id' => $coupon->company_id,
                'type' => CoinTransactionType::Spent,
                'amount' => $coupon->coin_cost,
                'coupon_redemption_id' => $redemption->id,
            ]))->forceFill($stamps)->save();
        }
    }
}
