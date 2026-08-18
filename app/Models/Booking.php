<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    /**
     * Grace period after start_time before an unclaimed shared-room
     * reservation counts as a no-show (Phase 5). Checked live by
     * isPastNoShowGrace() wherever correctness matters — the scheduled
     * sweep that flips status to 'no_show' is a cleanup pass, not the
     * source of truth, since a booking can be past-grace for a while
     * before the sweep next runs.
     */
    public const NO_SHOW_GRACE_MINUTES = 30;

    protected $fillable = [
        'owner_id',
        'room_id',
        'hotspot_user_id',
        'party_size',
        'checked_in_party_size',
        'booking_date',
        'start_time',
        'end_time',
        'price_per_hour',
        'total_hours',
        'total_price',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'price_per_hour' => 'decimal:2',
            'total_hours' => 'decimal:2',
            'total_price' => 'decimal:2',
            'party_size' => 'integer',
            'checked_in_party_size' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function hotspotUser(): BelongsTo
    {
        return $this->belongsTo(HotspotUser::class, 'hotspot_user_id');
    }

    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }

    /** Room charge plus any attached product sales. */
    public function grandTotal(): float
    {
        return (float) $this->total_price + (float) ($this->sale?->total ?? 0);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'confirmed' => 'blue',
            'checked_in' => 'teal',
            'completed' => 'green',
            'cancelled' => 'red',
            'no_show' => 'orange',
            default => 'gray',
        };
    }

    /**
     * Tailwind badge classes for statusColor(), as one string — the single
     * source of truth for status-pill styling. Several views used to keep
     * their own local color=>class lookup array instead of calling this,
     * and every one of them was missing 'teal'/'orange' (checked_in/no_show
     * silently rendered as the gray fallback instead of their real color) —
     * a real bug, not a hypothetical one. New/updated views should call
     * this directly rather than re-deriving a mapping from statusColor().
     */
    public function statusBadgeClass(): string
    {
        return match ($this->statusColor()) {
            'yellow' => 'bg-yellow-100 text-yellow-800',
            'blue' => 'bg-blue-100 text-blue-800',
            'teal' => 'bg-teal-100 text-teal-800',
            'green' => 'bg-green-100 text-green-800',
            'red' => 'bg-red-100 text-red-800',
            'orange' => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'checked_in' => 'Checked In',
            'no_show' => 'No Show',
            default => ucfirst($this->status),
        };
    }

    public function timeRange(): string
    {
        $range = Carbon::parse($this->start_time)->format('h:i A')
            .' - '
            .Carbon::parse($this->end_time)->format('h:i A');

        // Unicode bidi isolate (U+2066/U+2069): this is always LTR content
        // (digits + AM/PM), but plain ASCII digits/hyphens are direction-
        // neutral to the bidi algorithm, so embedded in an RTL page
        // (Arabic locale) it silently reorders — e.g. "07:00 PM - 08:00 PM"
        // renders as "PM - 08:00 PM 07:00". Isolating it here fixes every
        // call site at once rather than requiring dir="ltr" wrapping in
        // each of the five views that call this.
        return "\u{2066}{$range}\u{2069}";
    }

    /** The reservation's planned start as one instant, for grace-period math. */
    public function startsAt(): Carbon
    {
        return Carbon::parse($this->booking_date->format('Y-m-d').' '.$this->start_time);
    }

    /**
     * The reservation's planned end as one instant, for the auto-completion
     * sweep. Rolls to the next day if end_time <= start_time — current
     * validation always keeps start/end on the same calendar day, so this
     * branch shouldn't be reachable today, but it's cheap insurance against
     * ever silently computing a negative/zero duration if that constraint
     * loosens later.
     */
    public function endsAt(): Carbon
    {
        $start = $this->startsAt();
        $end = Carbon::parse($this->booking_date->format('Y-m-d').' '.$this->end_time);

        return $end->lte($start) ? $end->addDay() : $end;
    }

    /**
     * Pure time math: has this booking's no-show grace period elapsed?
     * Says nothing about status or room type — callers (the check-in flow,
     * availability queries, the no-show sweep) combine this with
     * status === 'confirmed' and room->isShared() as needed, so this stays
     * the one place the cutoff itself is computed.
     */
    public function isPastNoShowGrace(): bool
    {
        return $this->startsAt()->addMinutes(self::NO_SHOW_GRACE_MINUTES)->isPast();
    }

    /**
     * Seats reserved but never claimed, once checked in — null beforehand.
     * party_size stays the original reservation size (never overwritten at
     * check-in) specifically so this comparison remains possible later.
     */
    public function noShowSeats(): ?int
    {
        if ($this->checked_in_party_size === null) {
            return null;
        }

        return max(0, $this->party_size - $this->checked_in_party_size);
    }
}
