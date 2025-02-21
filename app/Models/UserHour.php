<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserHour extends Model
{
    use Auditable; // Trait'i ekle
    use HasFactory;

    protected $table = 'user_hours';

    protected $fillable = [
        'user_id',
        'morning_start_time',
        'morning_end_time',
        'evening_start_time',
        'evening_end_time',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
