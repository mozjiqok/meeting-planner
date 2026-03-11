<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'slot_id',
        'booking_date',
        'telegram_user_id',
        'telegram_username',
        'telegram_first_name',
        'answers',
        'meeting_url',
        'status',
        'reminder_24h_sent',
        'reminder_1h_sent',
    ];

    protected $casts = [
        'booking_date'     => 'date',
        'answers'          => 'array',
        'reminder_24h_sent'=> 'boolean',
        'reminder_1h_sent' => 'boolean',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    /** Returns the datetime of the call in Asia/Vladivostok */
    public function getCallDatetimeAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse(
            $this->booking_date->toDateString() . ' ' . $this->slot->start_time,
            config('app.timezone')
        );
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'confirmed')
            ->where('booking_date', '>=', now()->toDateString());
    }
}
