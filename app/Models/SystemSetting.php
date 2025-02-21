<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use Auditable; // Trait'i ekle
    use HasFactory;

    protected $table = 'system_settings';

    protected $fillable = [
        'setting_type',
        'morning_start_time',
        'morning_end_time',
        'evening_start_time',
        'evening_end_time',
        'version_link',
        'version_desc',
        'version_status',
    ];
}
