<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    public const TOPICS = ['account', 'coins', 'coupons', 'review', 'catalog', 'other'];

    public const STATUSES = ['open', 'waiting', 'resolved'];

    protected $fillable = ['user_id', 'subject', 'topic', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('id');
    }
}
