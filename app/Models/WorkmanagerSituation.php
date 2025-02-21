<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkmanagerSituation extends Model
{
    use Auditable; // Trait'i ekle
    use HasFactory;

    protected $table = 'workmanager_situation';

    protected $fillable = [
        'user_id',
        'active_hours',
        'is_active',
        'location_info',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
