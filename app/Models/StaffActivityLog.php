<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StaffActivityLog extends Model
{
    use HasFactory;

    /**
     * Append-only: rows are never updated, so there is no updated_at column.
     */
    const UPDATED_AT = null;

    protected $table = 'staff_activity_log';

    protected $fillable = [
        'owner_id',
        'staff_id',
        'actor_name',
        'actor_email',
        'actor_type',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id')->withTrashed();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
