<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemCity extends Model
{
    public $timestamps = false;

    protected $fillable = ['item_id', 'ibge_code', 'city', 'state'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
