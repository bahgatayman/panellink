<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
