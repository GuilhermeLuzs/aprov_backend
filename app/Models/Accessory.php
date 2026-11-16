<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accessory extends Model
{
    public const KIND_SKIN = 'skin';

    public const KIND_FRAME = 'frame';

    protected $fillable = ['slug', 'kind', 'asset', 'name', 'description', 'price', 'required_level', 'position'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'required_level' => 'integer'];
    }
}
