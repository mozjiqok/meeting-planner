<?php

namespace App\Telegram\Handlers;

use App\Models\Booking;
use App\Telegram\Conversations\BookingConversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

class UserCommandHandler
{
    /** /start — kicks off the booking conversation */
    public function start(Nutgram $bot): void
    {
        BookingConversation::begin($bot);
    }

    /** /mybooking — show the user's current booking */
    public function myBooking(Nutgram $bot): void
    {
        $booking = Booking::where('telegram_user_id', $bot->userId())
            ->where('status', 'confirmed')
            ->where('booking_date', '>=', now(config('app.timezone'))->toDateString())
            ->with('slot')
            ->first();

        if (!$booking) {
            $bot->sendMessage(
                "У вас нет активных записей.\n\nЧтобы записаться на звонок, отправьте /start",
                parse_mode: ParseMode::HTML
            );
            return;
        }

        $dt      = $booking->formatUserDatetime();
        $linkRow = $booking->meeting_url ? "\n🔗 <b>Ссылка:</b> {$booking->meeting_url}" : '';

        $bot->sendMessage(
            "📅 <b>Ваша запись:</b>\n\n" .
            "🗓 <b>{$dt}</b>\n" .
            "⏱ Длительность: {$booking->slot->duration_minutes} мин.{$linkRow}\n\n" .
            "Чтобы отменить, отправьте /cancel",
            parse_mode: ParseMode::HTML
        );
    }

    /** /cancel — cancel the user's booking */
    public function cancel(Nutgram $bot): void
    {
        $booking = Booking::where('telegram_user_id', $bot->userId())
            ->where('status', 'confirmed')
            ->where('booking_date', '>=', now(config('app.timezone'))->toDateString())
            ->first();

        if (!$booking) {
            $bot->sendMessage('У вас нет активных записей для отмены.');
            return;
        }

        $booking->update(['status' => 'cancelled']);
        $bot->sendMessage(
            "✅ Запись отменена.\n\nЧтобы записаться снова, отправьте /start"
        );

        // Notify Admin
        $adminId = config('nutgram.admin_id');
        if ($adminId) {
            $dt   = $booking->call_datetime->locale('ru')->isoFormat('D MMMM [в] HH:mm');
            $name = $booking->telegram_username ? "@{$booking->telegram_username}" : "{$booking->telegram_first_name} (ID: {$bot->userId()})";
            $bot->sendMessage(
                "❌ <b>Запись отменена пользователем</b>\n\n" .
                "🗓 <b>{$dt}</b>\n" .
                "👤 Клиент: {$name}",
                chat_id: $adminId,
                parse_mode: ParseMode::HTML
            );
        }
    }
}
