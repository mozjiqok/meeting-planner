<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'slot_id',
        'booking_date',
        'start_time',
        'duration_minutes',
        'telegram_user_id',
        'telegram_username',
        'telegram_first_name',
        'answers',
        'meeting_url',
        'status',
        'reminder_24h_sent',
        'reminder_1h_sent',
        'reminder_admin_sent',
    ];

    protected $casts = [
        'booking_date'     => 'date',
        'answers'          => 'array',
        'reminder_24h_sent'=> 'boolean',
        'reminder_1h_sent' => 'boolean',
        'reminder_admin_sent' => 'boolean',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    /** Returns the datetime of the call in Asia/Vladivostok */
    public function getCallDatetimeAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse(
            $this->booking_date->toDateString() . ' ' . $this->start_time,
            config('app.timezone')
        );
    }

    /** Returns the datetime of the call in User's timezone (from .env) */
    public function getUserDatetimeAttribute(): \Carbon\Carbon
    {
        return $this->call_datetime->copy()->setTimezone(config('app.user_timezone', 'Europe/Moscow'));
    }

    public function getFormattedTimeAttribute(): string
    {
        return substr($this->start_time, 0, 5);
    }

    /** Format datetime for user messages */
    public function formatUserDatetime(): string
    {
        $dt = $this->user_datetime;
        return $dt->locale('ru')->isoFormat('dddd, D MMMM [в] HH:mm (UTC Z, zz)');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'confirmed')
            ->where('booking_date', '>=', now()->toDateString());
    }
}
