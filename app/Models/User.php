<?php

namespace App\Models;

// Extends Laravel's default Authenticatable-based User model — keep
// whatever the fresh install generated (Notifiable trait etc.) and just
// add the pieces below into your existing app/Models/User.php:
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // 'admin' | 'guard'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isGuard(): bool
    {
        return $this->role === 'guard';
    }
}
