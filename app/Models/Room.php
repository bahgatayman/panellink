<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'is_available'   => 'boolean',
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
        return $this->bookings()
            ->where('booking_date', $date)
            ->where('status', '!=', 'cancelled')
            ->when($excludeBookingId, fn($q) => $q->where('id', '!=', $excludeBookingId))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q2) use ($startTime, $endTime) {
                    $q2->where('start_time', '<', $endTime)
                       ->where('end_time', '>', $startTime);
                });
            })
            ->exists();
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'meeting'  => 'Meeting Room',
            'training' => 'Training Room',
            'shared'   => 'Shared Space',
            'office'   => 'Private Office',
            default    => ucfirst($this->type),
        };
    }

    public function typeColor(): string
    {
        return match ($this->type) {
            'meeting'  => 'blue',
            'training' => 'purple',
            'shared'   => 'green',
            'office'   => 'orange',
            default    => 'gray',
        };
    }

    public function isShared(): bool
    {
        return $this->type === 'shared';
    }

    public function sharedSessions(): HasMany
    {
        return $this->hasMany(SharedSession::class);
    }

    public function openSharedSessions(): HasMany
    {
        return $this->sharedSessions()->where('status', 'open');
    }

    public function availableSharedSlots(): int
    {
        $openCount = $this->openSharedSessions()->count();
        return max(0, $this->capacity - $openCount);
    }

    public function isSharedFull(): bool
    {
        return $this->availableSharedSlots() === 0;
    }
}
