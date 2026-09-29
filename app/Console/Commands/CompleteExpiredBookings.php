<?php

namespace App\Console\Commands;

use App\Exceptions\CouponRejectedException;
use App\Exceptions\CouponUsageLimitExceededException;
use App\Models\Booking;
use App\Services\ActivityLogger;
use App\Services\CouponService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
 *
 * Bookings with no coupon attached (the overwhelming majority) still go
 * through the original fast bulk UPDATE. A coupon-attached booking needs its
 * usage redeemed exactly once at completion (see BookingController::
 * updateStatus()), so it's completed one row at a time via
 * completeWithCoupon() instead.
 */
class CompleteExpiredBookings extends Command
{
    protected $signature = 'bookings:complete-expired';

    protected $description = "Mark 'confirmed' exclusive-room bookings as 'completed' once their scheduled end time has passed";

    public function handle(CouponService $coupons, ActivityLogger $activityLogger): int
    {
        // Any confirmed, exclusive-room booking dated before today has
        // unconditionally already ended — no per-row time check needed, and
        // this avoids ever comparing raw time strings.
        $pastDates = Booking::where('status', 'confirmed')
            ->whereHas('room', fn ($q) => $q->where('type', '!=', 'shared'))
            ->whereDate('booking_date', '<', today())
            ->whereNull('coupon_id')
            ->update(['status' => 'completed']);

        $pastDatesWithCoupon = Booking::where('status', 'confirmed')
            ->whereHas('room', fn ($q) => $q->where('type', '!=', 'shared'))
            ->whereDate('booking_date', '<', today())
            ->whereNotNull('coupon_id')
            ->get(['id']);

        // Today's confirmed exclusive-room bookings need a precise per-row
        // check via Booking::endsAt() (Carbon, never a raw string compare —
        // end_time is a bare 'H:i' column with no cast).
        $todaysExpired = Booking::where('status', 'confirmed')
            ->whereHas('room', fn ($q) => $q->where('type', '!=', 'shared'))
            ->whereDate('booking_date', today())
            ->get(['id', 'booking_date', 'start_time', 'end_time', 'coupon_id'])
            ->filter(fn (Booking $booking) => $booking->endsAt()->isPast());

        $todaysExpiredIds = $todaysExpired->whereNull('coupon_id')->pluck('id');
        $todaysExpiredWithCoupon = $todaysExpired->whereNotNull('coupon_id');

        if ($todaysExpiredIds->isNotEmpty()) {
            Booking::whereIn('id', $todaysExpiredIds)->update(['status' => 'completed']);
        }

        $withCoupon = $pastDatesWithCoupon->concat($todaysExpiredWithCoupon);
        foreach ($withCoupon as $row) {
            $this->completeWithCoupon((int) $row->id, $coupons, $activityLogger);
        }

        $total = $pastDates + $todaysExpiredIds->count() + $withCoupon->count();
        $this->info("Completed {$total} expired booking(s).");

        return self::SUCCESS;
    }

    /**
     * The sweep has no human to ask, so unlike the manual completion path
     * (BookingController::updateStatus(), which deliberately blocks a
     * completion whose coupon is no longer valid) an invalid coupon here is
     * simply dropped and the booking completed anyway — it must never sit
     * stuck waiting on an owner who may not notice for days.
     */
    private function completeWithCoupon(int $bookingId, CouponService $coupons, ActivityLogger $activityLogger): void
    {
        try {
            DB::transaction(function () use ($bookingId, $coupons) {
                $claimed = Booking::where('id', $bookingId)->where('status', 'confirmed')->update(['status' => 'completed']);

                if ($claimed) {
                    $coupons->redeemForBooking(Booking::with('sale.items')->findOrFail($bookingId));
                }
            });
        } catch (CouponRejectedException|CouponUsageLimitExceededException $e) {
            $booking = Booking::findOrFail($bookingId);

            DB::transaction(function () use ($booking, $coupons) {
                $coupons->detachFromBooking($booking);
                Booking::where('id', $booking->id)->where('status', 'confirmed')->update(['status' => 'completed']);
            });

            $activityLogger->log(
                'booking.coupon_dropped',
                $booking,
                "Coupon dropped from booking #{$booking->id} during auto-completion: {$e->getMessage()}"
            );
        }
    }
}
