<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HalfdayRequest extends Model
{
    use Auditable; // Trait'i ekle
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'type',
        'reason',
        'days_count',
        'rapor_file',
        'status',
        'end_date', // yeni eklediğimiz alan
    ];

    // Bu modeli user ile ilişkilendiriyorsanız (belongsTo)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
