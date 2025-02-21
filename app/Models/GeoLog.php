<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeoLog extends Model
{
    use Auditable; // Trait'i ekle
    use HasFactory;
    protected $table = 'geo_logs';

    protected $fillable = [
        'user_id', 
        'lat', 
        'lng', 
        'status',
        'notification_go',
    ];
}
