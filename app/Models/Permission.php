<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'group',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'staff_permissions')
            ->withPivot('granted_at', 'granted_by_owner_id')
            ->withTimestamps();
    }
}
