<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeekendControl extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'all_users',
        'week_start_date',
        'weekend_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
