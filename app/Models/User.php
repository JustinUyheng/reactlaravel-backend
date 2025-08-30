<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'firstname',
        'lastname',
        'birthday',
        'gender',
        'email',
        'password',
        'role_id',
        'is_approved',
        'profile_picture',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'firstname' => 'string',
        'lastname' => 'string',
        'birthday' => 'date',
        'gender' => 'string',
        'email' => 'string',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role_id' => 'integer',
        'is_approved' => 'boolean',
    ];

    public function getProfilePictureUrlAttribute()
    {
        return $this->profile_picture 
            ? Storage::url($this->profile_picture)
            : null;
    }

    protected $appends = ['profile_picture_url'];

    public function store()
    {
        return $this->hasOne(Store::class);
    }

    public function isVendor()
    {
        return $this->role_id === 2;
    }

    public function isAdmin()
    {
        return $this->role_id === 3;
    }

    public function isUser()
    {
        return $this->role_id === 1;
    }
}
