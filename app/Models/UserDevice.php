<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    use Auditable; // Trait'i ekle
    
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_info',
        'cihaz_yetki',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
