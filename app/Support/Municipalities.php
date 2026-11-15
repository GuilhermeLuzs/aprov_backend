<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class Municipalities
{
    private static ?array $byCode = null;

    public static function find(int $ibgeCode): ?array
    {
        return self::all()[$ibgeCode] ?? null;
    }

    private static function all(): array
    {
        if (self::$byCode !== null) {
            return self::$byCode;
        }

        $data = json_decode(File::get(resource_path('data/municipalities.json')), true);
        self::$byCode = [];

        foreach ($data['cities'] as $state => $cities) {
            foreach ($cities as [$code, $name]) {
                self::$byCode[(int) $code] = ['ibge_code' => (string) $code, 'city' => $name, 'state' => $state];
            }
        }

        return self::$byCode;
    }
}
