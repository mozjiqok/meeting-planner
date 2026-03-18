<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannedUser extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'banned_until',
        'reason',
    ];

    protected $casts = [
        'banned_until' => 'datetime',
    ];

    public static function isBanned(int $telegramUserId): bool
    {
        return self::where('telegram_user_id', $telegramUserId)
            ->where(function ($query) {
                $query->whereNull('banned_until')
                    ->orWhere('banned_until', '>', now());
            })
            ->exists();
    }
}
