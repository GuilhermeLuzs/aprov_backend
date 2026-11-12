<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicFileUrl
{
    public static function of(?string $pathOrUrl): ?string
    {
        if ($pathOrUrl === null || $pathOrUrl === '') {
            return null;
        }

        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return $pathOrUrl;
        }

        return Storage::disk('public')->url($pathOrUrl);
    }
}
