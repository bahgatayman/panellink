<?php

namespace App\Models;

use App\Support\Duration;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Prepaid hours one member bought. Every term is a snapshot (independent of
 * the template). Status is derived, never stored. used_minutes changes only
 * through HourPackageService, which also writes the PackageUsage audit row.
 */
class MemberPackage extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_EXHAUSTED = 'exhausted';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'owner_id', 'hotspot_user_id', 'package_template_id', 'name', 'total_minutes', 'used_minutes',
        'price_paid', 'starts_on', 'expires_on', 'room_ids', 'notes', 'cancelled_at', 'cancelled_reason',
        'created_by_type', 'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'total_minutes' => 'integer',
            'used_minutes' => 'integer',
            'price_paid' => 'decimal:2',
            'starts_on' => 'date',
            'expires_on' => 'date',
            'room_ids' => 'array',
            'cancelled_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(HotspotUser::class, 'hotspot_user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PackageTemplate::class, 'package_template_id');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PackageUsage::class)->latest('id');
    }

    public function remainingMinutes(): int
    {
        return max(0, $this->total_minutes - $this->used_minutes);
    }

    /** cancelled → scheduled → expired → exhausted → active, as of $on (default today). */
    public function status(?Carbon $on = null): string
    {
        $on = ($on ?? now())->copy()->startOfDay();

        if ($this->cancelled_at) {
            return self::STATUS_CANCELLED;
        }
        if ($this->starts_on->gt($on)) {
            return self::STATUS_SCHEDULED;
        }
        if ($this->expires_on->lt($on)) {
            return self::STATUS_EXPIRED;
        }
        if ($this->remainingMinutes() <= 0) {
            return self::STATUS_EXHAUSTED;
        }

        return self::STATUS_ACTIVE;
    }

    /** Valid on that calendar date (inclusive) and not cancelled — the booking date is what counts. */
    public function isUsableOn(string|Carbon $date): bool
    {
        $d = Carbon::parse($date)->startOfDay();

        return ! $this->cancelled_at && $this->starts_on->lte($d) && $this->expires_on->gte($d);
    }

    public function allowsRoom(Room $room): bool
    {
        return empty($this->room_ids) || in_array($room->id, array_map('intval', $this->room_ids), true);
    }

    public function isExpiringSoon(?int $days = null): bool
    {
        $days ??= (int) config('packages.expiring_soon_days', 3);

        return $this->status() === self::STATUS_ACTIVE && $this->expires_on->lte(now()->startOfDay()->addDays($days));
    }

    public function progressPercent(): int
    {
        return $this->total_minutes > 0 ? (int) round($this->used_minutes / $this->total_minutes * 100) : 100;
    }

    public function remainingLabel(): string
    {
        return Duration::label($this->remainingMinutes());
    }

    public function totalLabel(): string
    {
        return Duration::label($this->total_minutes);
    }

    public function usedLabel(): string
    {
        return Duration::label($this->used_minutes);
    }

    public function statusTone(): string
    {
        return match ($this->status()) {
            self::STATUS_ACTIVE => $this->isExpiringSoon() ? 'warn' : 'ok',
            self::STATUS_SCHEDULED => 'info',
            self::STATUS_EXHAUSTED, self::STATUS_EXPIRED => 'neutral',
            default => 'danger',
        };
    }
}
