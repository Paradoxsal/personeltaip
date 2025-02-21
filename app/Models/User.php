<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use TheSeer\Tokenizer\Token;

class User extends Authenticatable
{
    use Auditable; // Trait'i ekle
    
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'kullanici_birimi',
        'phone',
        'location',
        'units_id',
        'about_me',
        'check_in_location',
        'check_out_location',
        'device_info',
        'cihaz_yetki',
        'fcm_role',
        'shift',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function tokensWithExpiry()
    {
        return $this->hasMany(Token::class)->where('expires_at', '>', now());
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

}
