<?php

namespace App\Models;

use App\Models\Departman;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Personel extends Model
{
    use Auditable; // Trait'i ekle
    use HasFactory;

    protected $table = 'users';  // Veritabanındaki tablo adı
    protected $fillable = ['name', 'password', 'kullanici_birimi'];


    public function Personel()
    {
        return $this->hasMany(Personel::class); // Kullanıcıya ait personelleri getirecek ilişki
    }

    public function department()
    {
        return $this->belongsTo(Departman::class);
    }
}
