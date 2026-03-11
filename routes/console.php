<?php

use App\Console\Commands\SendReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send reminders at :55 and :00 for 24h and 1h windows
Schedule::command(SendReminders::class)->everyMinute();
