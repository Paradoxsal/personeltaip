<?php

// app/Models/Departman.php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departman extends Model
{
    use Auditable; // Trait'i ekle
    use HasFactory;

    // Tablo ismini manuel olarak belirtiyoruz
    protected $table = 'departments';

    // Hangi alanların toplu atama (mass assignment) için güvenli olduğunu belirtiyoruz
    protected $fillable = ['birim_adi', 'birim_baskani', 'birim_locatino'];
}
