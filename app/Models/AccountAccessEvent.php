<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountAccessEvent extends Model
{
    public const REASONS = [
        'spam', 'fakeReview', 'offensive', 'harassment', 'fakeAccount', 'misleading', 'coupons', 'manipulation', 'registration', 'appeal', 'resolved', 'mistake', 'other',
    ];

    protected $fillable = ['user_id', 'applied_by', 'action', 'reason', 'note'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
