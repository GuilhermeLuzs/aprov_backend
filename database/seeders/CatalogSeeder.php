<?php

namespace Database\Seeders;

use App\Enums\ItemKind;
use App\Models\Item;
use App\Models\Tag;
use App\Support\Municipalities;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    private const SEED_ONLY_KEYS = ['id' => null, 'coins_reward' => null, 'xp_reward' => null, 'cities' => null];

    public function run(): void
    {
        $data = require database_path('data/catalog_seed.php');

        foreach ($data['items'] as $seed) {
            $this->seedItem($seed);
        }

        foreach ($data['tags'] as $tag) {
            Tag::query()->firstOrCreate(
                ['name' => $tag['label']],
                array_diff_key($tag, ['id' => null, 'label' => null]),
            );
        }
    }

    private function seedItem(array $seed): void
    {
        $companyId = (int) substr($seed['company_id'], 1);
        $attributes = ['company_id' => $companyId] + array_diff_key($seed, self::SEED_ONLY_KEYS);

        $item = Item::query()->firstOrNew(['company_id' => $companyId, 'title' => $seed['title']]);

        if (! $item->exists) {
            $item->fill($attributes)->save();
        } else {
            $item->update($this->missingDetails($item, $attributes));
        }

        if (($seed['cities'] ?? []) !== [] && ! $item->cities()->exists()) {
            $item->cities()->createMany(array_map(fn (int $code) => Municipalities::find($code), $seed['cities']));
        }
    }

    private function missingDetails(Item $item, array $attributes): array
    {
        $details = [];

        if ($item->brand === null && isset($attributes['brand'])) {
            $details['brand'] = $attributes['brand'];
        }

        if ($item->kind === ItemKind::Service && $item->duration_minutes === null) {
            $details += array_intersect_key($attributes, array_flip(['duration_minutes', 'recurrence', 'service_modality', 'cep']));
        }

        return $details;
    }
}
