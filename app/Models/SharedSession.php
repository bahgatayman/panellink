<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedSession extends Model
{
    protected $fillable = [
        'owner_id', 'room_id', 'hotspot_user_id',
        'session_date', 'start_time',
        'opened_at', 'closed_at', 'total_minutes', 'total_price',
        'status', 'booking_id',
    ];

    protected $casts = [
        'opened_at'     => 'datetime',
        'closed_at'     => 'datetime',
        'session_date'  => 'date',
        'total_minutes' => 'decimal:2',
        'total_price'   => 'decimal:2',
    ];

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

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function durationInMinutes(): float
    {
        $end = $this->closed_at ?? now();
        return round($this->opened_at->diffInSeconds($end) / 60, 2);
    }

    public function currentPrice(): float
    {
        $minutes = $this->durationInMinutes();
        $hours   = $minutes / 60;
        return round($hours * $this->room->price_per_hour, 2);
    }

    public function formattedDuration(): string
    {
        $minutes = (int) $this->durationInMinutes();
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return ($h > 0 ? $h . 'h ' : '') . $m . 'm';
    }
}
