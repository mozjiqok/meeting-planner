<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Slot extends Model
{
    protected $fillable = [
        'day_of_week',
        'start_time',
        'duration_minutes',
        'is_active',
        'default_meeting_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // 1=Mon ... 5=Fri
    public const DAY_NAMES = [
        1 => 'Понедельник',
        2 => 'Вторник',
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
        6 => 'Суббота',
        7 => 'Воскресенье',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(SlotBlock::class);
    }

    public function isBlockedOn(\Carbon\Carbon $date): bool
    {
        return $this->blocks()->whereDate('blocked_date', $date->toDateString())->exists();
    }

    public function isBookedOn(\Carbon\Carbon $date): bool
    {
        return $this->bookings()
            ->whereDate('booking_date', $date->toDateString())
            ->where('status', 'confirmed')
            ->exists();
    }

    public function getDayNameAttribute(): string
    {
        return self::DAY_NAMES[$this->day_of_week] ?? '';
    }

    public function getFormattedTimeAttribute(): string
    {
        return substr($this->start_time, 0, 5);
    }
}
