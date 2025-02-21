<?php

// app/Models/UserFcmToken.php
namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class UserFcmToken extends Model
{
    use Auditable; // Trait'i ekle
    protected $table = 'user_fcm_tokens';

    protected $fillable = [
        'user_id',
        'fcm_token',
        'device_info',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
