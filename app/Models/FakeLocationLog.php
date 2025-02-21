<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class FakeLocationLog extends Model
{
    use Auditable; // Trait'i ekle
    protected $table = 'fake_location_logs';

    protected $fillable = [
        'user_id',
        'user_name',
        'device_info',
        'fake_location',
        'real_location',
        'detected_at',
    ];
}

