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
            $item['company_id'] = (int) substr($item['company_id'], 1);
            Item::create(array_diff_key($item, ['id' => null, 'coins_reward' => null, 'xp_reward' => null]));
        }

        foreach ($data['tags'] as $tag) {
            $tag['name'] = $tag['label'];
            Tag::create(array_diff_key($tag, ['id' => null, 'label' => null]));
        }
    }
}
