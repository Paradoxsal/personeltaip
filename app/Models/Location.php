<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use Auditable; // Trait'i ekle
    use HasFactory;
    
    protected $table = 'locations'; // tablo adı
    protected $fillable = [
        'location_name',
        'location_address',
        'created_by',
        'users_id',
    ];
}
