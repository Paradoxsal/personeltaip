<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Holiday extends Model
{
    use Auditable; // Trait'i ekle
    use HasFactory;

    protected $fillable = [
        'holiday_name',
        'description',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * “diff_days” accessor:
     *
     * - Eğer status!='active' => 0
     * - Eğer today < start   => toplam gün = (end - start +1)
     * - Eğer start <= today <= end => kalan gün = (end - today +1)
     * - Eğer today > end => 0
     */
   
    // eğer eskiden user() varsa; ama tüm kullanıcılar ise zaten gerek yok:
    // public function user() { ... }
}
