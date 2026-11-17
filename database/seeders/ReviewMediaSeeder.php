<?php

namespace Database\Seeders;

use App\Enums\ItemKind;
use App\Models\Review;
use App\Support\SeedMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewMediaSeeder extends Seeder
{
    private const RANDOM_SEED = 20261118;

    private const SERVICE_SHARE = 45;

    private const VIDEO_SHARE = 30;

    private const MAX_PHOTOS = 3;

    public function run(): void
    {
        $library = require database_path('data/review_media_seed.php');
        mt_srand(self::RANDOM_SEED);

        Review::query()
            ->whereDoesntHave('allMedia')
            ->with('item')
            ->orderBy('id')
            ->chunkById(200, function ($reviews) use ($library) {
                DB::transaction(fn () => $reviews->each(fn (Review $review) => $this->attach($review, $library[$review->item_id] ?? [])));
            });
    }

    private function attach(Review $review, array $media): void
    {
        $product = $review->item?->kind === ItemKind::Product;
        if ($media === [] || (! $product && mt_rand(1, 100) > self::SERVICE_SHARE)) {
            return;
        }

        $photos = array_values(array_filter($media, fn (array $file) => $file['type'] === 'photo'));
        $videos = array_values(array_filter($media, fn (array $file) => $file['type'] === 'video'));
        $chosen = array_slice($this->shuffled($photos), 0, mt_rand(1, min(self::MAX_PHOTOS, count($photos))));

        if ($videos !== [] && mt_rand(1, 100) <= self::VIDEO_SHARE) {
            $chosen[] = $videos[mt_rand(0, count($videos) - 1)];
        }

        foreach ($chosen as $position => $file) {
            $review->allMedia()->create([
                'type' => $file['type'],
                'path' => SeedMedia::publish($file['path']),
                'cover_path' => isset($file['cover_path']) ? SeedMedia::publish($file['cover_path']) : null,
                'valid' => true,
                'width' => $file['width'],
                'height' => $file['height'],
                'duration_seconds' => $file['duration_seconds'] ?? null,
                'position' => $position,
            ]);
        }
    }

    private function shuffled(array $items): array
    {
        for ($index = count($items) - 1; $index > 0; $index--) {
            $swap = mt_rand(0, $index);
            [$items[$index], $items[$swap]] = [$items[$swap], $items[$index]];
        }

        return $items;
    }
}
