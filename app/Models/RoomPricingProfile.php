<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An owner-named alternative hourly rate for one room ("Photography — 700/hr").
 * Choosing it prices a booking/session per hour at this rate instead of the
 * room's default pricing (RoomPricingService). Bookings/sessions snapshot the
 * rate and name, so editing or removing a profile never changes history.
 */
class RoomPricingProfile extends Model
{
    protected $fillable = ['owner_id', 'room_id', 'name', 'price_per_hour', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'price_per_hour' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** "EGP 700.00/hr" */
    public function rateLabel(): string
    {
        return Money::format((float) $this->price_per_hour).__('app.common.slash_hr');
    }

    /** Used by bookings/sessions → keep (deactivate) rather than delete. */
    public function isUsed(): bool
    {
        return Booking::where('room_pricing_profile_id', $this->id)->exists()
            || SharedSession::where('room_pricing_profile_id', $this->id)->exists();
    }
}
