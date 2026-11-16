<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    public const EARNED = 'earned';

    public const SPENT = 'spent';

    protected $fillable = ['customer_id', 'type', 'amount', 'review_id', 'accessory_id'];
}
