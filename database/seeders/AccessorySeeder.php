<?php

namespace Database\Seeders;

use App\Models\Accessory;
use Illuminate\Database\Seeder;

class AccessorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (require database_path('data/accessories_seed.php') as $seed) {
            Accessory::query()->updateOrCreate(['slug' => $seed['slug']], $seed);
        }
    }
}
