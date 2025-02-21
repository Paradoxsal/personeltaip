<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkmanagerLog extends Model
{
    use Auditable; // Trait'i ekle
    use HasFactory;

    protected $table = 'workmanager_logs';

    protected $fillable = [
        'user_id',
        'date',
        'sendMorningGunaydin',
        'checkGiris09',
        'checkGiris11',
        'checkGiris12_20',
        'checkCikis1655',
        'checkCikis1715',
        'checkCikisAfter1720',
        'checkNoRecords2130',
        'workmanager_start',
        // created_at, updated_at -> Laravel default
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
