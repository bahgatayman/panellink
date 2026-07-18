<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotspotUser extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'phone',
        'password',
        'speed_download',
        'speed_upload',
        'status',
        'speed_profile_id',
        'email',
        'notes',
    ];

    protected $hidden = [
        'password',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function speedProfile(): BelongsTo
    {
        return $this->belongsTo(SpeedProfile::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'hotspot_user_id');
    }

    public function sharedSessions(): HasMany
    {
        return $this->hasMany(SharedSession::class, 'hotspot_user_id');
    }

    public function hasOpenSharedSession(): bool
    {
        return $this->sharedSessions()->where('status', 'open')->exists();
    }
}
