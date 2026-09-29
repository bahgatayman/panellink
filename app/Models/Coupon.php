<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FIXED = 'fixed';

    public const SCOPE_ROOMS = 'rooms';

    public const SCOPE_PRODUCTS = 'products';

    public const SCOPE_BOTH = 'both';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_LIMIT_REACHED = 'limit_reached';

    protected $fillable = [
        'owner_id',
        'code',
        'discount_type',
        'discount_value',
        'applies_to',
        'starts_at',
        'expires_at',
        'usage_limit',
        'per_customer_limit',
        'minimum_spend',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'minimum_spend' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'usage_limit' => 'integer',
            'per_customer_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'coupon_rooms')->withTimestamps();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_products')->withTimestamps();
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function appliesTo(string $type): bool
    {
        return $this->applies_to === self::SCOPE_BOTH || $this->applies_to === $type;
    }

    /** Zero targeted rooms means "all rooms" — derived, never a stored flag. */
    public function targetsAllRooms(): bool
    {
        return $this->appliesTo(self::SCOPE_ROOMS) && $this->rooms()->count() === 0;
    }

    public function targetsAllProducts(): bool
    {
        return $this->appliesTo(self::SCOPE_PRODUCTS) && $this->products()->count() === 0;
    }

    public function coversRoom(int $roomId): bool
    {
        if (! $this->appliesTo(self::SCOPE_ROOMS)) {
            return false;
        }

        return $this->targetsAllRooms() || $this->rooms->contains('id', $roomId);
    }

    /** A null $productId (a removed product, snapshotted line item) only counts under an "all products" coupon. */
    public function coversProduct(?int $productId): bool
    {
        if (! $this->appliesTo(self::SCOPE_PRODUCTS)) {
            return false;
        }

        if ($productId === null) {
            return $this->targetsAllProducts();
        }

        return $this->targetsAllProducts() || $this->products->contains('id', $productId);
    }

    public function usedCount(): int
    {
        return $this->usages_count ?? $this->usages()->count();
    }

    public function remainingUses(): ?int
    {
        if ($this->usage_limit === null) {
            return null;
        }

        return max(0, $this->usage_limit - $this->usedCount());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isScheduled(): bool
    {
        return $this->starts_at !== null && $this->starts_at->isFuture();
    }

    public function isUsageLimitReached(): bool
    {
        return $this->usage_limit !== null && $this->usedCount() >= $this->usage_limit;
    }

    /**
     * Precedence: inactive beats every other reason (an owner's explicit
     * off-switch is always shown first), then expired, then limit-reached,
     * then scheduled. None of these are stored — all derived from existing
     * fields, per the feature's own "no redundant status fields" rule.
     */
    public function statusKey(): string
    {
        if (! $this->is_active) {
            return self::STATUS_INACTIVE;
        }
        if ($this->isExpired()) {
            return self::STATUS_EXPIRED;
        }
        if ($this->isUsageLimitReached()) {
            return self::STATUS_LIMIT_REACHED;
        }
        if ($this->isScheduled()) {
            return self::STATUS_SCHEDULED;
        }

        return self::STATUS_ACTIVE;
    }

    public function statusLabel(): string
    {
        return __('app.coupons.status_'.$this->statusKey());
    }

    public function statusTone(): string
    {
        return match ($this->statusKey()) {
            self::STATUS_ACTIVE => 'ok',
            self::STATUS_SCHEDULED => 'info',
            self::STATUS_EXPIRED, self::STATUS_LIMIT_REACHED => 'danger',
            default => 'neutral',
        };
    }

    public function discountLabel(): string
    {
        return $this->discount_type === self::TYPE_PERCENTAGE
            ? number_format((float) $this->discount_value, (float) $this->discount_value == (int) $this->discount_value ? 0 : 1).'%'
            : 'ج.م '.number_format((float) $this->discount_value, 2);
    }

    /** @param Builder<Coupon> $query */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        $now = now();

        return match ($status) {
            self::STATUS_INACTIVE => $query->where('is_active', false),
            self::STATUS_EXPIRED => $query->where('is_active', true)->whereNotNull('expires_at')->where('expires_at', '<', $now),
            self::STATUS_LIMIT_REACHED => $query->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now))
                ->whereNotNull('usage_limit')
                ->whereRaw('(select count(*) from coupon_usages where coupon_usages.coupon_id = coupons.id) >= coupons.usage_limit'),
            self::STATUS_SCHEDULED => $query->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now))
                ->whereNotNull('starts_at')->where('starts_at', '>', $now),
            self::STATUS_ACTIVE => $query->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now))
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                ->where(fn ($q) => $q->whereNull('usage_limit')
                    ->orWhereRaw('(select count(*) from coupon_usages where coupon_usages.coupon_id = coupons.id) < coupons.usage_limit')),
            default => $query,
        };
    }
}
