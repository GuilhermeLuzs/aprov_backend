<?php

namespace App\Console\Commands;

use App\Models\ItemMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PrunePendingItemMedia extends Command
{
    protected $signature = 'items:prune-pending-media';

    protected $description = 'Apaga as mídias de item enviadas e nunca salvas num item';

    public function handle(): int
    {
        $expired = ItemMedia::query()
            ->whereNull('item_id')
            ->where('created_at', '<', now()->subHours(config('aprov.pending_media_hours')));

        $count = 0;

        $expired->chunkById(100, function ($media) use (&$count) {
            Storage::disk('public')->delete($media->flatMap(fn (ItemMedia $file) => array_filter([$file->path, $file->cover_path]))->all());
            ItemMedia::query()->whereIn('id', $media->modelKeys())->delete();
            $count += $media->count();
        });

        $this->info("{$count} mídias pendentes apagadas.");

        return self::SUCCESS;
    }
}
