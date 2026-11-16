<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModerationAssignment extends Model
{
    protected $fillable = ['queue', 'target_key', 'user_id'];
}
