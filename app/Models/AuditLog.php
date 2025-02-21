<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{

    protected $fillable = [
        'table_name',
        'action',
        'old_data',
        'new_data',
        'performed_by',
        'performed_on',
    ];

    // İlişkiler
    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function performedOn()
    {
        return $this->belongsTo(User::class, 'performed_on');
    }
}