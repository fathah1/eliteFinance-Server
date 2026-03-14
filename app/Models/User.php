<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'account_owner_id',
        'is_super_user',
        'username',
        'name',
        'email',
        'phone',
        'shop_name',
        'settings',
        'password',
        'pin_code',
        'pin_code_lookup',
        'offline_auth_salt',
        'offline_auth_version',
    ];

    protected $hidden = [
        'password',
        'pin_code',
        'pin_code_lookup',
        'offline_auth_salt',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
            'is_super_user' => 'boolean',
        ];
    }

    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'account_owner_id');
    }

    public function staff()
    {
        return $this->hasMany(User::class, 'account_owner_id');
    }
}
