<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Regenerate owner alerts hourly (the layout also refreshes on-demand, throttled).
Schedule::command('notifications:refresh')->hourly();

// Auto-complete exclusive-room bookings once their scheduled end time has
// passed. Shared rooms are excluded — see CompleteExpiredBookings' docblock.
Schedule::command('bookings:complete-expired')->everyFifteenMinutes();
