<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

// Redirect root to admin
Route::redirect('/', '/admin');

/*
|--------------------------------------------------------------------------
| Admin Panel — protected by HTTP Basic Auth
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware('auth.basic')->group(function () {
    // Dashboard / summary
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Slots management
    Route::get('/slots', [DashboardController::class, 'slotsIndex'])->name('admin.slots');
    Route::post('/slots', [DashboardController::class, 'storeSlot'])->name('admin.slots.store');
    Route::patch('/slots/{slot}', [DashboardController::class, 'updateSlot'])->name('admin.slots.update');
    Route::delete('/slots/{slot}', [DashboardController::class, 'deleteSlot'])->name('admin.slots.delete');
    Route::post('/slots/block', [DashboardController::class, 'blockSlot'])->name('admin.slots.block');
    Route::delete('/slots/block/{slotBlock}', [DashboardController::class, 'unblockSlot'])->name('admin.slots.unblock');

    // Bookings management
    Route::get('/bookings', [DashboardController::class, 'bookingsIndex'])->name('admin.bookings');
    Route::delete('/bookings/{booking}', [DashboardController::class, 'cancelBooking'])->name('admin.bookings.cancel');
    Route::patch('/bookings/{booking}/url', [DashboardController::class, 'updateMeetingUrl'])->name('admin.bookings.url');

    // Bans management
    Route::post('/bans', [DashboardController::class, 'banUser'])->name('admin.bans.store');
    Route::delete('/bans/{bannedUser}', [DashboardController::class, 'unbanUser'])->name('admin.bans.destroy');
});

// Telegram Webhook
Route::post('/telegram/webhook', function (\SergiX44\Nutgram\Nutgram $bot) {
    $bot->run();
});
