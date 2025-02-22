<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivityLog extends Model
{
    protected $table = 'user_activity_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'location',
        'event',
        'logged_at',
    ];
}
