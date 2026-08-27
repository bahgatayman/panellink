<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Staff extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        'owner_id',
        'role_id',
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'staff_permissions')
            ->withPivot('granted_at', 'granted_by_owner_id')
            ->withTimestamps();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(StaffActivityLog::class);
    }

    public function hasPermission(string $key): bool
    {
        return $this->permissions()->where('key', $key)->where('is_active', true)->exists();
    }

    /**
     * Replace this staff member's grants with their role's default bundle.
     * Used both at creation time and by the "reset to role defaults" action.
     */
    public function syncPermissionsFromRole(): void
    {
        if (! $this->role) {
            $this->permissions()->sync([]);

            return;
        }

        $grants = $this->role->permissions()->pluck('permissions.id')
            ->mapWithKeys(fn ($id) => [$id => [
                'granted_at' => now(),
                'granted_by_owner_id' => $this->owner_id,
            ]])
            ->all();

        $this->permissions()->sync($grants);
    }
}
