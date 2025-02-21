<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends Model
{
    use Auditable; // Trait'i ekle
    use HasFactory;
    
    protected $table = 'personal_access_tokens'; // tablo adı
    protected $fillable = [
        'name',
        'token',
    ];
}
