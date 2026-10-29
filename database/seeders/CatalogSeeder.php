<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('data/catalog_seed.php');

        foreach ($data['items'] as $item) {
            Item::create(array_diff_key($item, ['id' => null]));
        }

        foreach ($data['tags'] as $tag) {
            Tag::create(array_diff_key($tag, ['id' => null]));
        }
    }
}
