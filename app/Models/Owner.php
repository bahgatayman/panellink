<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Owner extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'business_name',
        'mikrotik_host',
        'mikrotik_port',
        'mikrotik_username',
        'mikrotik_password',
    ];

    protected $hidden = [
        'password',
        'mikrotik_password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'mikrotik_port' => 'integer',
            'password' => 'hashed',
        ];
    }

    public function hotspotUsers(): HasMany
    {
        return $this->hasMany(HotspotUser::class);
    }
}
