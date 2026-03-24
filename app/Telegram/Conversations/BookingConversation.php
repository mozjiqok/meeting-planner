<?php

namespace App\Telegram\Conversations;

use App\Models\Booking;
use App\Models\Slot;
use Carbon\Carbon;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class BookingConversation extends Conversation
{
    protected ?string $step = 'start';

    // Accumulated answers
    public ?string $answerQ1 = null;
    public ?string $answerQ2 = null;
    public ?string $answerQ3 = null;

    // Chosen slot
    public ?int $selectedSlotId   = null;
    public ?string $selectedDate   = null;

    /*──────────────────────────────────────────────────────────────
     *  Entry point
     *──────────────────────────────────────────────────────────────*/
    public function start(Nutgram $bot): void
    {
        // Check channel membership
        if (!$this->isChannelMember($bot)) {
            $channel = config('nutgram.channel_username');
            $bot->sendMessage(
                "❌ <b>Доступ закрыт.</b>\n\nЧтобы записаться на звонок, сначала подпишитесь на канал {$channel}.",
                parse_mode: ParseMode::HTML
            );
            $this->end();
            return;
        }

        // Check if user already has an upcoming booking
        $existing = Booking::where('telegram_user_id', $bot->userId())
            ->where('status', 'confirmed')
            ->where('booking_date', '>=', now(config('app.timezone'))->toDateString())
            ->with('slot')
            ->first();

        if ($existing) {
            $dt = $existing->formatUserDatetime();
            $bot->sendMessage(
                "📅 У вас уже есть запись на звонок:\n\n<b>{$dt}</b>\n\nЧтобы отменить, отправьте /cancel",
                parse_mode: ParseMode::HTML
            );
            $this->end();
            return;
        }

        $this->askQ1($bot);
    }

    /*──────────────────────────────────────────────────────────────
     *  Q1 — main problem
     *──────────────────────────────────────────────────────────────*/
    private function askQ1(Nutgram $bot): void
    {
        $bot->sendMessage(
            "👋 <b>Запись на звонок</b>\n\n" .
            "Опишите вашу главную проблему или вопрос одним предложением:",
            parse_mode: ParseMode::HTML
        );
        $this->next('handleQ1');
    }

    public function handleQ1(Nutgram $bot): void
    {
        $text = trim($bot->message()?->text ?? '');
        if (empty($text)) {
            $bot->sendMessage('⚠️ Пожалуйста, напишите ответ текстом.');
            return;
        }
        $this->answerQ1 = $text;
        $this->showSlotPicker($bot);
    }

    /*──────────────────────────────────────────────────────────────
     *  Q2 — have you studied the archive? (buttons)
     *──────────────────────────────────────────────────────────────*/
    private function askQ2(Nutgram $bot): void
    {
        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('✅ Да, изучил(а)', callback_data: 'q2:yes'),
                InlineKeyboardButton::make('❌ Нет', callback_data: 'q2:no'),
            );

        $bot->sendMessage(
            "<b>Вопрос 2 из 3</b>\n" .
            "Вы изучили архив канала перед тем, как записаться на звонок?",
            parse_mode: ParseMode::HTML,
            reply_markup: $keyboard
        );
        $this->next('handleQ2');
    }

    public function handleQ2(Nutgram $bot): void
    {
        $callbackData = $bot->callbackQuery()?->data;

        if ($callbackData === 'q2:yes') {
            $this->answerQ2 = 'Да';
            $bot->answerCallbackQuery();
            $this->askQ3($bot);
        } elseif ($callbackData === 'q2:no') {
            $this->answerQ2 = 'Нет';
            $bot->answerCallbackQuery();
            $channel = config('nutgram.channel_username');
            $bot->sendMessage(
                "📚 Рекомендуем изучить архив {$channel} — там есть ответы на многие вопросы.\n\n" .
                "Но если ваш вопрос уникальный — продолжим!"
            );
            $this->askQ3($bot);
        } else {
            // They sent a text message instead of pressing button
            $bot->sendMessage('👆 Пожалуйста, используйте кнопки выше.');
        }
    }

    /*──────────────────────────────────────────────────────────────
     *  Q3 — expected outcome
     *──────────────────────────────────────────────────────────────*/
    private function askQ3(Nutgram $bot): void
    {
        $bot->sendMessage(
            "<b>Вопрос 3 из 3</b>\n" .
            "Какого конкретного результата вы ждёте от этого звонка?",
            parse_mode: ParseMode::HTML
        );
        $this->next('handleQ3');
    }

    public function handleQ3(Nutgram $bot): void
    {
        $text = trim($bot->message()?->text ?? '');
        if (empty($text)) {
            $bot->sendMessage('⚠️ Пожалуйста, напишите ответ текстом.');
            return;
        }
        $this->answerQ3 = $text;
        $this->showSlotPicker($bot);
    }

    /*──────────────────────────────────────────────────────────────
     *  Slot picker
     *──────────────────────────────────────────────────────────────*/
    private function showSlotPicker(Nutgram $bot): void
    {
        $tz     = config('app.timezone');
        $userTz = config('app.user_timezone');
        $now    = Carbon::now($tz);
        $slots  = Slot::where('is_active', true)->get();

        if ($slots->isEmpty()) {
            $bot->sendMessage('😔 К сожалению, активных слотов для записи пока нет. Попробуйте позже.');
            $this->end();
            return;
        }

        $keyboard = InlineKeyboardMarkup::make();
        $found    = 0;
        $days     = 0;

        $slotsLimit = config('app.booking_slots_limit', 20);
        $daysLimit  = config('app.booking_days_limit', 30);

        while ($found < $slotsLimit && $days < $daysLimit) {
            $days++;
            $date = $now->copy()->addDays($days);
            $dow  = (int) $date->format('N'); // 1=Mon 7=Sun

            foreach ($slots as $slot) {
                if ($slot->day_of_week !== $dow) {
                    continue;
                }

                // Skip if in the past (same day but past the slot time)
                $slotDt = Carbon::parse($date->toDateString() . ' ' . $slot->start_time, $tz);
                if ($slotDt->isPast()) {
                    continue;
                }

                // Skip if blocked or already booked
                if ($slot->isBlockedOn($date) || $slot->isBookedOn($date)) {
                    continue;
                }

                $userSlotDt = $slotDt->copy()->setTimezone($userTz);
                $label = $userSlotDt->locale('ru')->isoFormat('D MMM (ddd), HH:mm');

                $keyboard->addRow(
                    InlineKeyboardButton::make(
                        $label,
                        callback_data: "slot:{$slot->id}:{$date->toDateString()}"
                    )
                );
                $found++;
            }
        }

        if ($found === 0) {
            $bot->sendMessage('😔 Свободных слотов в ближайшее время нет. Попробуйте позже.');
            $this->end();
            return;
        }

        $nowUser  = $now->copy()->setTimezone($userTz);
        $tzOffset = $nowUser->isoFormat('UTC Z, zz');
        $bot->sendMessage(
            "✅ <b>Отлично!</b>\n\n" .
            "Выберите удобное время для звонка\n(указано по {$tzOffset}):",
            parse_mode: ParseMode::HTML,
            reply_markup: $keyboard
        );
        $this->next('handleSlotChoice');
    }

    public function handleSlotChoice(Nutgram $bot): void
    {
        $data = $bot->callbackQuery()?->data;

        if (!$data || !str_starts_with($data, 'slot:')) {
            $bot->sendMessage('👆 Пожалуйста, выберите слот из кнопок выше.');
            return;
        }

        [, $slotId, $date] = explode(':', $data);
        $this->selectedSlotId = (int) $slotId;
        $this->selectedDate   = $date;

        $bot->answerCallbackQuery();
        $this->showConfirmation($bot);
    }

    /*──────────────────────────────────────────────────────────────
     *  Confirmation
     *──────────────────────────────────────────────────────────────*/
    private function showConfirmation(Nutgram $bot): void
    {
        $slot = Slot::find($this->selectedSlotId);
        if (!$slot) {
            $bot->sendMessage('❌ Слот не найден. Начните заново с /start');
            $this->end();
            return;
        }

        $bookingDt = Carbon::parse($this->selectedDate . ' ' . $slot->start_time, config('app.timezone'));
        $dt = $bookingDt->copy()->setTimezone(config('app.user_timezone'))->locale('ru')->isoFormat('dddd, D MMMM [в] HH:mm (UTC Z, zz)');

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make('✅ Подтвердить', callback_data: 'confirm:yes'),
            InlineKeyboardButton::make('🔄 Выбрать другое', callback_data: 'confirm:no'),
        );

        $bot->sendMessage(
            "📋 <b>Подтвердите запись:</b>\n\n" .
            "🗓 <b>{$dt}</b>\n" .
            "⏱ Длительность: {$slot->duration_minutes} мин.\n\n" .
            "Всё верно?",
            parse_mode: ParseMode::HTML,
            reply_markup: $keyboard
        );
        $this->next('handleConfirmation');
    }

    public function handleConfirmation(Nutgram $bot): void
    {
        $data = $bot->callbackQuery()?->data;

        if ($data === 'confirm:yes') {
            $bot->answerCallbackQuery();
            $this->saveBooking($bot);
        } elseif ($data === 'confirm:no') {
            $bot->answerCallbackQuery();
            $this->selectedSlotId = null;
            $this->selectedDate   = null;
            $this->showSlotPicker($bot);
        } else {
            $bot->sendMessage('👆 Пожалуйста, используйте кнопки выше.');
        }
    }

    /*──────────────────────────────────────────────────────────────
     *  Save and notify
     *──────────────────────────────────────────────────────────────*/
    private function saveBooking(Nutgram $bot): void
    {
        $slot = Slot::find($this->selectedSlotId);

        // Race-condition check
        if ($slot->isBlockedOn(Carbon::parse($this->selectedDate)) || $slot->isBookedOn(Carbon::parse($this->selectedDate))) {
            $bot->sendMessage('😔 К сожалению, этот слот только что заняли. Выберите другое время:');
            $this->selectedSlotId = null;
            $this->selectedDate   = null;
            $this->showSlotPicker($bot);
            return;
        }

        // $jitsiRoom = 'mozg_vl-' . \Illuminate\Support\Str::random(12);
        // $jitsiUrl  = "https://meet.jit.si/{$jitsiRoom}";

        $booking = Booking::create([
            'slot_id'             => $this->selectedSlotId,
            'booking_date'        => $this->selectedDate,
            'start_time'          => $slot->start_time,
            'duration_minutes'    => $slot->duration_minutes,
            'telegram_user_id'    => $bot->userId(),
            'telegram_username'   => $bot->user()?->username,
            'telegram_first_name' => $bot->user()?->first_name,
            'answers'             => [
                'q1' => $this->answerQ1,
                'q2' => $this->answerQ2,
                'q3' => $this->answerQ3,
            ],
            'meeting_url'         => '', //$jitsiUrl,
            'status'              => 'confirmed',
        ]);

        $dt = $booking->formatUserDatetime();
        $linkText = $booking->meeting_url
            ? "\n\n🔗 <b>Ссылка на звонок:</b> {$booking->meeting_url}"
            : '';

        $bot->sendMessage(
            "🎉 <b>Запись подтверждена!</b>\n\n" .
            "📅 <b>{$dt}</b>{$linkText}\n\n" .
            "Я напомню вам за 24 часа и за 1 час до звонка.\n\n" .
            "Чтобы отменить запись, отправьте /cancel",
            parse_mode: ParseMode::HTML
        );

        // Notify Admin
        $adminId = config('nutgram.admin_id');
        if ($adminId) {
            $adminDt = $booking->call_datetime->locale('ru')->isoFormat('D MMMM [в] HH:mm');
            $userLink = $booking->telegram_username ? "@{$booking->telegram_username}" : "{$booking->telegram_first_name} (ID: {$booking->telegram_user_id})";
            $bot->sendMessage(
                "🆕 <b>Новая запись!</b>\n\n" .
                "🗓 <b>{$adminDt}</b>\n" .
                "👤 Клиент: {$userLink}\n" .
                "❓ Вопрос: {$booking->answers['q1']}",
                chat_id: $adminId,
                parse_mode: ParseMode::HTML
            );
        }

        $this->end();
    }

    /*──────────────────────────────────────────────────────────────
     *  Helper: check channel membership
     *──────────────────────────────────────────────────────────────*/
    private function isChannelMember(Nutgram $bot): bool
    {
        $channel = config('nutgram.channel_username');
        if (!$channel) {
            return true; // No channel configured → open to all
        }

        try {
            $member = $bot->getChatMember(
                chat_id: $channel,
                user_id: $bot->userId()
            );
            return in_array($member?->status, ['creator', 'administrator', 'member'], true);
        } catch (\Throwable) {
            return false;
        }
    }
}
