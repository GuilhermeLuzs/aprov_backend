<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(TestAccountSeeder::class);
        $this->call(CompanySeeder::class);
        $this->call(CompanyCoinRuleSeeder::class);
        $this->call(CatalogSeeder::class);
    }
}
