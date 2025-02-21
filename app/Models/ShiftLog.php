<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ShiftLog extends Model
{
    use Auditable; // Trait'i ekle
    use HasFactory;

    protected $table = 'shift_logs';

    protected $fillable = [
        'user_id',
        'shift_date',
        'is_on_shift',
        'no_shift_reason',
        'exit_time',
    ];

    // İlişki: 1 shift_log satırı, 1 user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
