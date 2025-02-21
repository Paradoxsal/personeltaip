<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualNotification extends Model
{
    use Auditable; // Trait'i ekle
    use HasFactory;

    // Tablo adı (eğer Laravel’in varsayılan "manual_notifications" ile eşleşiyorsa belirtmeye gerek yok)
    protected $table = 'manual_notifications';

    protected $fillable = [
        'title',
        'body',
        'scheduled_at',
        'target_type',
        'action',
        'user_id',
        'status',
        'sent_at',
    ];

    // Tarih sütunlarını otomatik olarak Carbon instance’ına çevirsin
    protected $dates = [
        'scheduled_at',
        'sent_at',
        'created_at',
        'updated_at',
    ];
}

