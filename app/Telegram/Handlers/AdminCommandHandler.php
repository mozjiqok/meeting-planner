<?php

namespace App\Telegram\Handlers;

use App\Models\Booking;
use App\Models\Slot;
use App\Models\SlotBlock;
use Carbon\Carbon;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

class AdminCommandHandler
{
    private function isAdmin(Nutgram $bot): bool
    {
        return (string) $bot->userId() === (string) config('nutgram.admin_id');
    }

    /** /slots — list upcoming slots with occupancy */
    public function slots(Nutgram $bot): void
    {
        if (!$this->isAdmin($bot)) {
            return;
        }

        $tz    = config('app.timezone');
        $today = Carbon::now($tz)->toDateString();
        $end   = Carbon::now($tz)->addDays(config('app.booking_days_limit'))->toDateString();

        $allSlots = Slot::where('is_active', true)->orderBy('day_of_week')->orderBy('start_time')->get();
        $bookings = Booking::where('status', 'confirmed')
            ->whereBetween('booking_date', [$today, $end])
            ->with('slot')
            ->get()
            ->keyBy(fn ($b) => $b->slot_id . '_' . $b->booking_date->toDateString());

        $blocks = SlotBlock::whereBetween('blocked_date', [$today, $end])
            ->get()
            ->groupBy('slot_id');

        $daysLimit = config('app.booking_days_limit');
        $lines = ["<b>📅 Слоты на ближайшие {$daysLimit} дней:</b>\n"];

        $tz      = config('app.timezone');
        $nowDate = Carbon::now($tz);

        for ($d = 1; $d <= $daysLimit; $d++) {
            $date = $nowDate->copy()->addDays($d);
            $dow  = (int) $date->format('N');
            $dayLabel = $date->locale('ru')->isoFormat('D MMM (ddd)');

            $daySlots = $allSlots->where('day_of_week', $dow);
            if ($daySlots->isEmpty()) {
                continue;
            }

            $lines[] = "\n<b>{$dayLabel}</b>";
            foreach ($daySlots as $slot) {
                $key     = $slot->id . '_' . $date->toDateString();
                $blocked = isset($blocks[$slot->id]) && $blocks[$slot->id]->contains(fn($b) => $b->blocked_date->toDateString() === $date->toDateString());
                $booking = $bookings->get($key);

                if ($blocked) {
                    $lines[] = "  🔒 {$slot->formatted_time} — <i>заблокировано</i>";
                } elseif ($booking) {
                    $name = $booking->telegram_username ? "@{$booking->telegram_username}" : $booking->telegram_first_name;
                    $lines[] = "  ✅ {$slot->formatted_time} — {$name}";
                } else {
                    $lines[] = "  🟢 {$slot->formatted_time} — свободно";
                }
            }
        }

        $bot->sendMessage(implode("\n", $lines), parse_mode: ParseMode::HTML);
    }

    /** /bookings — list upcoming bookings with answers */
    public function bookings(Nutgram $bot): void
    {
        if (!$this->isAdmin($bot)) {
            return;
        }

        $tz    = config('app.timezone');
        $today = now($tz)->toDateString();

        $bookings = Booking::where('status', 'confirmed')
            ->where('booking_date', '>=', $today)
            ->with('slot')
            ->orderBy('booking_date')
            ->get();

        if ($bookings->isEmpty()) {
            $bot->sendMessage('Нет предстоящих записей.');
            return;
        }

        foreach ($bookings as $booking) {
            $dt      = $booking->call_datetime->locale('ru')->isoFormat('dddd, D MMMM [в] HH:mm (UTC Z, zz)');
            $name    = $booking->telegram_username ? "@{$booking->telegram_username}" : $booking->telegram_first_name;
            $answers = $booking->answers;

            $bot->sendMessage(
                "📌 <b>#{$booking->id}</b> — {$dt}\n" .
                "👤 {$name}\n\n" .
                "❓ <i>Вопрос:</i>\n{$answers['q1']}\n\n" .
                "🔗 <i>Ссылка:</i>\n{$booking->meeting_url}",
                parse_mode: ParseMode::HTML
            );
        }
    }

    /**
     * /block DD.MM.YYYY HH:MM  — block a specific slot instance
     * Example: /block 15.03.2026 07:30
     */
    public function block(Nutgram $bot): void
    {
        if (!$this->isAdmin($bot)) {
            return;
        }

        $args = $this->parseArgs($bot);
        if (!$args) {
            $bot->sendMessage('Использование: /block DD.MM.YYYY HH:MM');
            return;
        }

        ['date' => $date, 'time' => $time, 'slot' => $slot] = $args;
        if (!$slot) {
            $bot->sendMessage("Слот {$time} не найден.");
            return;
        }

        SlotBlock::firstOrCreate(['slot_id' => $slot->id, 'blocked_date' => $date]);
        $dow = Carbon::parse($date, config('app.timezone'))->locale('ru')->isoFormat('D MMMM (ddd)');
        $bot->sendMessage("🔒 Слот {$time} {$dow} заблокирован.");
    }

    /**
     * /unblock DD.MM.YYYY HH:MM — unblock a slot
     */
    public function unblock(Nutgram $bot): void
    {
        if (!$this->isAdmin($bot)) {
            return;
        }

        $args = $this->parseArgs($bot);
        if (!$args) {
            $bot->sendMessage('Использование: /unblock DD.MM.YYYY HH:MM');
            return;
        }

        ['date' => $date, 'time' => $time, 'slot' => $slot] = $args;
        if (!$slot) {
            $bot->sendMessage("Слот {$time} не найден.");
            return;
        }

        SlotBlock::where('slot_id', $slot->id)->whereDate('blocked_date', $date)->delete();
        $dow = Carbon::parse($date, config('app.timezone'))->locale('ru')->isoFormat('D MMMM (ddd)');
        $bot->sendMessage("✅ Слот {$time} {$dow} разблокирован.");
    }

    /*──────────────────────────────────────────────────────────────
     *  Helper: parse "DD.MM.YYYY HH:MM" from command text
     *──────────────────────────────────────────────────────────────*/
    private function parseArgs(Nutgram $bot): ?array
    {
        $text  = $bot->message()?->text ?? '';
        $parts = preg_split('/\s+/', trim($text));
        // parts[0] = command, parts[1] = date, parts[2] = time
        if (count($parts) < 3) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('d.m.Y', $parts[1], config('app.timezone'))->toDateString();
            $time = $parts[2]; // e.g. "07:30"
        } catch (\Throwable) {
            return null;
        }

        // Convert HH:MM to HH:MM:SS for DB comparison
        $timeFull = $time . ':00';
        $dow      = (int) Carbon::parse($date, config('app.timezone'))->format('N');
        $slot     = Slot::where('day_of_week', $dow)
            ->where('start_time', $timeFull)
            ->first();

        return ['date' => $date, 'time' => $time, 'slot' => $slot];
    }
}
