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
    protected $description = 'Send 24h, 1h user reminders and 15m admin reminder';

    public function handle(Nutgram $bot): int
    {
        $tz  = config('app.timezone');
        $now = Carbon::now($tz);

        // Fetch all upcoming confirmed bookings that still need at least one reminder
        $bookings = Booking::where('status', 'confirmed')
            ->where('booking_date', '>=', $now->toDateString())
            ->where(function ($q) {
                $q->where('reminder_24h_sent', false)
                  ->orWhere('reminder_1h_sent', false)
                  ->orWhere('reminder_admin_sent', false);
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

            // 15-minute ADMIN reminder window: between 10m and 20m before
            if (
                !$booking->reminder_admin_sent
                && $now->diffInMinutes($callDt, false) <= 20
                && $now->diffInMinutes($callDt, false) >= 10
            ) {
                $this->sendAdminReminder($bot, $booking);
                $booking->update(['reminder_admin_sent' => true]);
            }
        }

        return self::SUCCESS;
    }

    private function sendReminder(Nutgram $bot, Booking $booking, string $timeLabel): void
    {
        $dt      = $booking->call_datetime->locale('ru')->isoFormat('D MMMM [в] HH:mm (UTC Z, zz)');
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

    private function sendAdminReminder(Nutgram $bot, Booking $booking): void
    {
        $adminId = config('nutgram.admin_id');
        if (!$adminId) {
            return;
        }

        $dt   = $booking->call_datetime->locale('ru')->isoFormat('HH:mm');
        $name = $booking->telegram_username ? "@{$booking->telegram_username}" : $booking->telegram_first_name;
        $ans  = $booking->answers;

        $msg = "⚡️ <b>Скоро звонок!</b> (в {$dt})\n\n" .
               "👤 <b>Клиент:</b> {$name}\n" .
               "❓ <b>Вопрос:</b>\n<i>{$ans['q1']}</i>";

        if (!empty($ans['q2'])) {
            $msg .= "\n\n📚 <b>Архив изучен:</b> {$ans['q2']}";
        }
        if (!empty($ans['q3'])) {
            $msg .= "\n🎯 <b>Ожидание:</b>\n<i>{$ans['q3']}</i>";
        }

        $msg .= "\n\n🔗 <b>Ссылка:</b>\n{$booking->meeting_url}";

        try {
            $bot->sendMessage(
                chat_id: $adminId,
                text: $msg,
                parse_mode: ParseMode::HTML
            );
        } catch (\Throwable $e) {
            $this->warn("Failed to send admin reminder to {$adminId}: {$e->getMessage()}");
        }
    }
}
