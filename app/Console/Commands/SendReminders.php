<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

class SendReminders extends Command
{
    protected $signature   = 'reminders:send';
    protected $description = 'Send 24h and 1h reminders for upcoming bookings';

    public function handle(Nutgram $bot): int
    {
        $tz  = config('app.timezone');
        $now = Carbon::now($tz);

        // Fetch all upcoming confirmed bookings that still need at least one reminder
        $bookings = Booking::where('status', 'confirmed')
            ->where('booking_date', '>=', $now->toDateString())
            ->where(function ($q) {
                $q->where('reminder_24h_sent', false)
                  ->orWhere('reminder_1h_sent', false);
            })
            ->with('slot')
            ->get();

        foreach ($bookings as $booking) {
            $callDt = $booking->call_datetime;

            // 24-hour reminder window: between 23h55m and 24h05m before
            if (
                !$booking->reminder_24h_sent
                && $now->diffInMinutes($callDt, false) <= 1440 + 5
                && $now->diffInMinutes($callDt, false) >= 1440 - 5
            ) {
                $this->sendReminder($bot, $booking, '24 часа');
                $booking->update(['reminder_24h_sent' => true]);
            }

            // 1-hour reminder window: between 55m and 65m before
            if (
                !$booking->reminder_1h_sent
                && $now->diffInMinutes($callDt, false) <= 65
                && $now->diffInMinutes($callDt, false) >= 55
            ) {
                $this->sendReminder($bot, $booking, '1 час');
                $booking->update(['reminder_1h_sent' => true]);
            }
        }

        return self::SUCCESS;
    }

    private function sendReminder(Nutgram $bot, Booking $booking, string $timeLabel): void
    {
        $dt      = $booking->call_datetime->locale('ru')->isoFormat('D MMMM [в] HH:mm');
        $linkRow = $booking->meeting_url ? "\n\n🔗 <b>Ссылка:</b> {$booking->meeting_url}" : '';

        try {
            $bot->sendMessage(
                chat_id: $booking->telegram_user_id,
                text:
                    "⏰ <b>Напоминание!</b>\n\n" .
                    "Через <b>{$timeLabel}</b> у вас запланирован звонок:\n" .
                    "🗓 <b>{$dt}</b>{$linkRow}\n\n" .
                    "Чтобы отменить — /cancel",
                parse_mode: ParseMode::HTML
            );
        } catch (\Throwable $e) {
            $this->warn("Failed to send reminder to {$booking->telegram_user_id}: {$e->getMessage()}");
        }
    }
}
