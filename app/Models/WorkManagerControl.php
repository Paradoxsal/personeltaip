<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkManagerControl extends Model
{
    protected $fillable = [
        'user_id',
        'pause',
        'pause_duration',
        'resume_at',
    ];

    protected $casts = [
        'pause' => 'boolean',
        'resume_at' => 'datetime',
    ];
}