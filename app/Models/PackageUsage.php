<?php

namespace App\Models;

use App\Support\Duration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One immutable change to a member package's balance (written only by HourPackageService). */
class PackageUsage extends Model
{
    public const PURCHASE = 'purchase';

    public const BOOKING_USAGE = 'booking_usage';

    public const BOOKING_ADJUSTMENT = 'booking_adjustment';

    public const BOOKING_CANCELLATION = 'booking_cancellation';

    public const SESSION_USAGE = 'session_usage';

    protected $fillable = [
        'owner_id', 'member_package_id', 'hotspot_user_id', 'booking_id', 'shared_session_id', 'action',
        'minutes', 'balance_before', 'balance_after', 'value', 'note', 'created_by_type', 'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'minutes' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
            'value' => 'decimal:2',
        ];
    }

    public function memberPackage(): BelongsTo
    {
        return $this->belongsTo(MemberPackage::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function label(): string
    {
        return __('app.packages.actions.'.$this->action);
    }

    /** "−2h" for hours used, "+1h" for hours returned (the balance's point of view). */
    public function changeLabel(): string
    {
        if ($this->action === self::PURCHASE) {
            return '+'.Duration::label($this->balance_after);
        }
        if ($this->minutes === 0) {
            return '—';
        }

        return ($this->minutes > 0 ? '−' : '+').Duration::label(abs($this->minutes));
    }
}
