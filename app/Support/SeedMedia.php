<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SeedMedia
{
    public static function publish(string $path): string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            $disk->put($path, File::get(database_path("seed-media/{$path}")));
        }

        return $path;
    }
}
