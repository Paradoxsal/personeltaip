<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Units extends Model
{
    use Auditable; // Trait'i ekle
    
    // Tablo ismini manuel olarak belirtiyoruz
    protected $table = 'units';

    // Hangi alanların toplu atama (mass assignment) için güvenli olduğunu belirtiyoruz
    protected $fillable = ['unit_name', 'unit_head', 'unit_location'];
}
