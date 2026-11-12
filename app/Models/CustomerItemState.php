<?php

namespace App\Models;

use App\Enums\ItemState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerItemState extends Model
{
    protected $fillable = ['customer_id', 'item_id', 'type'];

    protected function casts(): array
    {
        return ['type' => ItemState::class];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
