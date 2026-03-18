<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BannedUser;
use App\Models\Slot;
use App\Models\SlotBlock;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $tz    = config('app.timezone');
        $today = now($tz)->toDateString();

        $upcomingBookings = Booking::where('status', 'confirmed')
            ->where('booking_date', '>=', $today)
            ->with('slot')
            ->orderBy('booking_date')
            ->orderBy('slot_id')
            ->get();

        $slots = Slot::where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('admin.dashboard', compact('upcomingBookings', 'slots'));
    }

    // ── Slots ────────────────────────────────────────────────────

    public function slotsIndex()
    {
        $tz    = config('app.timezone');
        $today = now($tz)->toDateString();
        $end   = now($tz)->addDays(14)->toDateString();

        $slots    = Slot::orderBy('day_of_week')->orderBy('start_time')->get();
        $blocks   = SlotBlock::whereBetween('blocked_date', [$today, $end])->with('slot')->orderBy('blocked_date')->get();
        $bookings = Booking::where('status', 'confirmed')
            ->whereBetween('booking_date', [$today, $end])
            ->with('slot')
            ->orderBy('booking_date')
            ->get();

        return view('admin.slots', compact('slots', 'blocks', 'bookings'));
    }

    public function updateSlot(Request $request, Slot $slot)
    {
        $validated = $request->validate([
            'start_time'          => 'required|date_format:H:i',
            'duration_minutes'    => 'required|integer|min:5|max:480',
            'default_meeting_url' => 'nullable|url|max:500',
            'is_active'           => 'boolean',
        ]);

        $slot->update([
            'start_time'          => $validated['start_time'],
            'duration_minutes'    => $validated['duration_minutes'],
            'default_meeting_url' => $validated['default_meeting_url'] ?? null,
            'is_active'           => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Слот обновлён.');
    }

    public function blockSlot(Request $request)
    {
        $validated = $request->validate([
            'slot_id'      => 'required|exists:slots,id',
            'blocked_date' => 'required|date|after_or_equal:today',
            'reason'       => 'nullable|string|max:255',
        ]);

        SlotBlock::firstOrCreate(
            ['slot_id' => $validated['slot_id'], 'blocked_date' => $validated['blocked_date']],
            ['reason' => $validated['reason'] ?? null]
        );

        return back()->with('success', 'Слот заблокирован.');
    }

    public function unblockSlot(SlotBlock $slotBlock)
    {
        $slotBlock->delete();
        return back()->with('success', 'Блокировка снята.');
    }

    // ── Bookings ─────────────────────────────────────────────────

    public function bookingsIndex()
    {
        $tz    = config('app.timezone');
        $today = now($tz)->toDateString();

        $upcoming = Booking::where('status', 'confirmed')
            ->where('booking_date', '>=', $today)
            ->with('slot')
            ->orderBy('booking_date')
            ->get();

        $past = Booking::where('booking_date', '<', $today)
            ->orWhere('status', 'cancelled')
            ->with('slot')
            ->orderByDesc('booking_date')
            ->limit(50)
            ->get();

        $bannedUserIds = BannedUser::where(function ($query) {
            $query->whereNull('banned_until')
                ->orWhere('banned_until', '>', now());
        })->pluck('telegram_user_id')->toArray();

        return view('admin.bookings', compact('upcoming', 'past', 'bannedUserIds'));
    }

    public function banUser(Request $request)
    {
        $validated = $request->validate([
            'telegram_user_id' => 'required|integer',
            'banned_until'     => 'nullable|date|after:now',
            'reason'           => 'nullable|string|max:255',
        ]);

        BannedUser::updateOrCreate(
            ['telegram_user_id' => $validated['telegram_user_id']],
            [
                'banned_until' => $validated['banned_until'] ? Carbon::parse($validated['banned_until']) : null,
                'reason'       => $validated['reason'] ?? null,
            ]
        );

        return back()->with('success', 'Пользователь забанен.');
    }

    public function unbanUser(Request $request)
    {
        $validated = $request->validate([
            'telegram_user_id' => 'required|integer',
        ]);

        BannedUser::where('telegram_user_id', $validated['telegram_user_id'])->delete();

        return back()->with('success', 'Пользователь разбанен.');
    }

    public function cancelBooking(Booking $booking)
    {
        $booking->update(['status' => 'cancelled']);

        // Notify the user via Telegram
        try {
            $bot = app(\SergiX44\Nutgram\Nutgram::class);
            $dt  = $booking->call_datetime->locale('ru')->isoFormat('D MMMM [в] HH:mm');
            $bot->sendMessage(
                chat_id: $booking->telegram_user_id,
                text: "❌ Ваша запись на звонок <b>{$dt}</b> была отменена администратором.\n\nЧтобы записаться снова — /start",
                parse_mode: \SergiX44\Nutgram\Telegram\Properties\ParseMode::HTML
            );
        } catch (\Throwable) {
            // Silently ignore if user blocked the bot
        }

        return back()->with('success', 'Запись отменена, пользователь уведомлён.');
    }

    public function updateMeetingUrl(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'meeting_url' => 'nullable|url|max:500',
        ]);

        $booking->update(['meeting_url' => $validated['meeting_url']]);
        return back()->with('success', 'Ссылка обновлена.');
    }
}
