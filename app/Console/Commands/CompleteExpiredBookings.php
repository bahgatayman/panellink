<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

/**
 * Auto-completes exclusive-room (meeting/training/office) 'confirmed'
 * bookings once their scheduled end time has passed. Shared rooms are
 * deliberately excluded: a shared room's 'confirmed' status means "reserved,
 * awaiting check-in," with its own lifecycle (checkIn() -> checked_in ->
 * SharedSessionController::close() -> completed, priced from actual elapsed
 * time). An abandoned shared reservation nobody checked into is a no-show,
 * not a fulfilled booking — auto-completing it here would misrecord it as a
 * paid, fulfilled booking. That's a separate, still-unbuilt no-show sweep.
 *
 * Idempotent by construction: the WHERE status='confirmed' clause naturally
 * excludes rows a previous run already flipped, so re-running is always
 * safe and a no-op once nothing is left eligible.
 */
class CompleteExpiredBookings extends Command
{
    protected $signature = 'bookings:complete-expired';

    protected $description = "Mark 'confirmed' exclusive-room bookings as 'completed' once their scheduled end time has passed";

    public function handle(): int
    {
        // Any confirmed, exclusive-room booking dated before today has
        // unconditionally already ended — no per-row time check needed, and
        // this avoids ever comparing raw time strings.
        $pastDates = Booking::where('status', 'confirmed')
            ->whereHas('room', fn ($q) => $q->where('type', '!=', 'shared'))
            ->whereDate('booking_date', '<', today())
            ->update(['status' => 'completed']);

        // Today's confirmed exclusive-room bookings need a precise per-row
        // check via Booking::endsAt() (Carbon, never a raw string compare —
        // end_time is a bare 'H:i' column with no cast).
        $todaysExpiredIds = Booking::where('status', 'confirmed')
            ->whereHas('room', fn ($q) => $q->where('type', '!=', 'shared'))
            ->whereDate('booking_date', today())
            ->get(['id', 'booking_date', 'start_time', 'end_time'])
            ->filter(fn (Booking $booking) => $booking->endsAt()->isPast())
            ->pluck('id');

        if ($todaysExpiredIds->isNotEmpty()) {
            Booking::whereIn('id', $todaysExpiredIds)->update(['status' => 'completed']);
        }

        $total = $pastDates + $todaysExpiredIds->count();
        $this->info("Completed {$total} expired booking(s).");

        return self::SUCCESS;
    }
}
