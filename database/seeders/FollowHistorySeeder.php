<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FollowHistorySeeder extends Seeder
{
    private const RANDOM_SEED = 20261119;

    private const EMAIL_DOMAIN = '%@historico.aprov.teste';

    private const MIN_FOLLOWING = 2;

    private const MAX_FOLLOWING = 9;

    public function run(): void
    {
        $customers = Customer::query()->whereHas('user', fn ($user) => $user->where('email', 'like', self::EMAIL_DOMAIN))->orderBy('id')->pluck('id')->all();
        $everyone = Customer::query()->orderBy('id')->pluck('id')->all();

        if ($customers === [] || DB::table('follows')->whereIn('follower_id', $customers)->exists()) {
            return;
        }

        mt_srand(self::RANDOM_SEED);
        $rows = [];

        foreach ($customers as $follower) {
            $pool = array_values(array_diff($everyone, [$follower]));
            for ($index = count($pool) - 1; $index > 0; $index--) {
                $swap = mt_rand(0, $index);
                [$pool[$index], $pool[$swap]] = [$pool[$swap], $pool[$index]];
            }
            foreach (array_slice($pool, 0, mt_rand(self::MIN_FOLLOWING, self::MAX_FOLLOWING)) as $followed) {
                $rows[] = ['follower_id' => $follower, 'followed_id' => $followed, 'created_at' => now(), 'updated_at' => now()];
            }
        }

        DB::table('follows')->insert($rows);
    }
}
