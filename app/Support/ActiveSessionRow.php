<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Room;
use App\Models\Sale;
use App\Models\SharedSession;
use Carbon\Carbon;

/**
 * One card's worth of data, wrapping whichever underlying record it came
 * from rather than flattening everything into scalars — the two card
 * partials still reach through to the real SharedSession/Booking model for
 * anything type-specific (billing_unit, total_price, etc.), this class only
 * adds the handful of things that are genuinely cross-cutting.
 */
class ActiveSessionRow
{
    private function __construct(
        public readonly string $type, // 'shared' | 'exclusive'
        public readonly SharedSession|Booking $model,
        public readonly Room $room,
        public readonly HotspotUser $customer,
        public readonly ?Sale $sale,
    ) {}

    public static function fromSharedSession(SharedSession $session): self
    {
        return new self('shared', $session, $session->room, $session->hotspotUser, $session->sale);
    }

    public static function fromBooking(Booking $booking): self
    {
        return new self('exclusive', $booking, $booking->room, $booking->hotspotUser, $booking->sale);
    }

    public function isShared(): bool
    {
        return $this->type === 'shared';
    }

    public function productCount(): int
    {
        return $this->sale?->items->count() ?? 0;
    }

    /** When this session/booking actually started, for a common "started at" display. */
    public function startedAt(): Carbon
    {
        return $this->isShared() ? $this->model->opened_at : $this->model->startsAt();
    }

    /** Shared-only: whether this session was checked in from a reservation, or opened as a walk-in. */
    public function isFromBooking(): bool
    {
        return $this->isShared() && $this->model->booking_id !== null;
    }
}
