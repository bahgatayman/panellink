<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Lang;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'owner_id',
        'name',
        'type',
        'capacity',
        'price_per_hour',
        'description',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'price_per_hour' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function hasConflict(string $date, string $startTime, string $endTime, ?int $excludeBookingId = null): bool
    {
        // whereDate(), not where('booking_date', $date): the `date` cast
        // serializes to 'Y-m-d H:i:s' on write (Eloquent's date-format
        // config isn't cast-type-aware), so a bare 'Y-m-d' equality check
        // silently never matches on engines with no native DATE coercion
        // (SQLite stores the literal string; MySQL's real DATE column type
        // happens to truncate it back to a bare date on insert, which is
        // why this was invisible in a MySQL-backed environment).
        return $this->bookings()
            ->whereDate('booking_date', $date)
            ->where('status', '!=', 'cancelled')
            ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q2) use ($startTime, $endTime) {
                    $q2->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            })
            ->exists();
    }

    /** Bilingual label; unknown types fall back to the raw value. */
    public function typeLabel(): string
    {
        $key = 'app.room_type.'.$this->type;

        return Lang::has($key) ? __($key) : ucfirst((string) $this->type);
    }

    public function typeColor(): string
    {
        return match ($this->type) {
            'meeting' => 'blue',
            'training' => 'purple',
            'shared' => 'green',
            'office' => 'orange',
            default => 'gray',
        };
    }

    public function isShared(): bool
    {
        return $this->type === 'shared';
    }

    /**
     * Capacity that's actually enforceable for booking-conflict purposes.
     * Non-shared room types are always exclusive (1), regardless of whatever
     * raw `capacity` value is stored — that column is decorative and
     * unvalidated per-type today (RoomController accepts 1-999 for any type),
     * so pinning the effective value here means a fat-fingered capacity can
     * never silently make an exclusive room double-bookable.
     */
    public function effectiveCapacity(): int
    {
        return $this->isShared() ? $this->capacity : 1;
    }

    public function sharedSessions(): HasMany
    {
        return $this->hasMany(SharedSession::class);
    }

    public function openSharedSessions(): HasMany
    {
        return $this->sharedSessions()->where('status', 'open');
    }

    /**
     * Seats free right now. Sums each open session's party_size rather than
     * counting rows — a party of 5 must consume 5 seats, not 1 — so a room
     * doesn't silently accept more people than it physically holds. Safe for
     * pre-party-size data: every legacy session has party_size=1, so the sum
     * equals the old row count for any data that predates this field.
     */
    public function availableSharedSlots(): int
    {
        $occupiedSeats = (int) $this->openSharedSessions()->sum('party_size');

        return max(0, $this->capacity - $occupiedSeats);
    }

    public function isSharedFull(): bool
    {
        return $this->availableSharedSlots() === 0;
    }
}
