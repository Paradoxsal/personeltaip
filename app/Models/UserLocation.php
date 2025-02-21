<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLocation extends Model
{
    use Auditable; // Trait'i ekle
    use HasFactory;

    protected $table = 'user_locations';

    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'timestamp',
    ];
}
