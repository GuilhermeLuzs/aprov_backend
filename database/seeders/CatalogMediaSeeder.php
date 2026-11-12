<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Item;
use App\Support\SeedMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CatalogMediaSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = require database_path('data/catalog_seed.php');
        $companies = require database_path('data/companies_seed.php');
        $media = require database_path('data/catalog_media_seed.php');

        foreach ($catalog['items'] as $index => $seed) {
            $item = Item::query()->where('title', $seed['title'])->first();

            if (! $item || $item->media()->exists()) {
                continue;
            }

            foreach ($media['items'][$index] ?? [] as $position => $file) {
                $item->media()->create([
                    'type' => $file['type'],
                    'path' => SeedMedia::publish($file['path']),
                    'cover_path' => isset($file['cover_path']) ? SeedMedia::publish($file['cover_path']) : null,
                    'width' => $file['width'] ?? null,
                    'height' => $file['height'] ?? null,
                    'duration_seconds' => $file['duration_seconds'] ?? null,
                    'position' => $position,
                ]);
            }
        }

        foreach ($companies as $index => $seed) {
            $company = Company::query()->where('name', $seed['name'])->first();
            $logo = $media['logos'][$index] ?? null;

            if ($company && $logo && ! Storage::disk('public')->exists((string) $company->logo_url)) {
                $company->update(['logo_url' => SeedMedia::publish($logo)]);
            }
        }
    }
}
