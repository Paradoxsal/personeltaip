<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use Auditable; // Trait'i ekle

    use HasFactory;

    // Tablonun adını belirtelim
    protected $table = 'attendances';

    // Doldurulabilir alanları tanımlayalım
    protected $fillable = [
        'user_id',
        'user_name',
        'check_in_time',
        'check_in_location',
        'check_out_time',
        'check_out_location',
    ];

    // Tarih sütunlarını otomatik olarak yönetmek istersek (örneğin `created_at`, `updated_at`)
    public $timestamps = true;

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];

    // Opsiyonel: Kullanıcı ile ilişki tanımlanabilir
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
