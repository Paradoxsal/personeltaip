<?php
namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    protected static function bootAuditable()
    {
        // created event'i
        static::created(function ($model) {
            self::logAction($model, 'create');
        });

        // updated event'i
        static::updated(function ($model) {
            self::logAction($model, 'update');
        });

        // deleted event'i
        static::deleted(function ($model) {
            self::logAction($model, 'delete');
        });
    }

    private static function logAction($model, string $action)
    {
        // Eski ve yeni verileri al
        $oldData = $action === 'update' || $action === 'delete' ? $model->getOriginal() : null;
        $newData = $action !== 'delete' ? $model->getAttributes() : null;

        // Hassas alanları filtrele (şifre gibi)
        $sensitiveFields = ['password', 'remember_token'];
        $oldData = self::filterSensitiveData($oldData, $sensitiveFields);
        $newData = self::filterSensitiveData($newData, $sensitiveFields);

        // AuditLog'a kaydet
        AuditLog::create([
            'table_name' => $model->getTable(), // Tablo adı
            'action' => $action,
            'old_data' => $oldData ? json_encode($oldData) : null,
            'new_data' => $newData ? json_encode($newData) : null,
            'performed_by' => Auth::id(), // Kim yaptı
            'performed_on' => $model->id, // Kime yapıldı
        ]);
    }

    private static function filterSensitiveData($data, array $sensitiveFields)
    {
        if (!$data) {
            return null;
        }

        // Hassas alanları filtrele
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '******'; // Hassas veriyi maskele
            }
        }

        return $data;
    }
}