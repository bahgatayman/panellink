<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    protected $fillable = [
        'coupon_id',
        'owner_id',
        'booking_id',
        'hotspot_user_id',
        'original_amount',
        'discount_amount',
        'final_amount',
        'room_discount',
        'product_discount',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'room_discount' => 'decimal:2',
            'product_discount' => 'decimal:2',
            'used_at' => 'datetime',
        ];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function hotspotUser(): BelongsTo
    {
        return $this->belongsTo(HotspotUser::class);
    }
}
