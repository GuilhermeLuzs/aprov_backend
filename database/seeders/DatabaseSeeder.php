<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(CompanySeeder::class);
        $this->call(AccessorySeeder::class);
        $this->call(TestAccountSeeder::class);
        $this->call(CompanyCoinRuleSeeder::class);
        $this->call(CatalogSeeder::class);
        $this->call(CatalogMediaSeeder::class);
        $this->call(CommunitySeeder::class);
        $this->call(ReviewHistorySeeder::class);
        $this->call(FollowHistorySeeder::class);
        $this->call(ReviewMediaSeeder::class);
        $this->call(CoinHistorySeeder::class);
        $this->call(CouponSeeder::class);
        $this->call(ModerationSeeder::class);
    }
}
