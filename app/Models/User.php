<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
// use Laragear\WebAuthn\WebAuthnAuthentication;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $guarded = ['id'];

    public function quests(): HasMany
    {
        return $this->hasMany(Quest::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(Progress::class);
    }
}
