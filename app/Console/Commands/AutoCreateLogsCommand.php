<?php 

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\User;
use App\Models\WorkmanagerLog;
use App\Models\WorkmanagerSituation;
use App\Models\Holiday;       // Örnek tatil tablosu
use App\Models\HalfdayRequest; // Örnek izin/rapor tablosu

class AutoCreateLogsCommand extends Command
{
    /**
     * Bu komutun Artisan’da görünecek signature’ı:
     * php artisan auto:create-logs
     */
    protected $signature = 'auto:create-logs';

    /**
     * Komutun kısa açıklaması
     */
    protected $description = 'Sabah 08:10 gibi, tüm kullanıcılar için workmanager_logs ve workmanager_situation kaydı oluşturur';

    /**
     * Komutun asıl işi burada
     */
    public function handle()
    {
        $today = Carbon::now()->format('Y-m-d');

        // Tüm kullanıcıları al
        $users = User::all();

        foreach ($users as $user) {
            // 1) Bugün o kullanıcı için “off” (hafta sonu / tatil / izin) mi?
            if ($this->checkIfOff($user->id, $today)) {
                // off ise bu kullanıcıya tablo oluşturmayalım, devam
                continue;
            }

            // 2) workmanager_logs tablosu => bugünün kaydı var mı?
            $logExists = WorkmanagerLog::where('user_id', $user->id)
                ->where('date', $today)
                ->first();

            if (!$logExists) {
                // Yoksa => oluştur
                $log = new WorkmanagerLog();
                $log->user_id = $user->id;
                $log->date = $today;
                // sendMorningGunaydin = 1 (sabah bayrağı)
                $log->sendMorningGunaydin = 1;
                $log->save();
            }

            // 3) workmanager_situation => bugünün kaydı var mı?
            $sitExists = WorkmanagerSituation::where('user_id', $user->id)
                ->whereDate('created_at', $today)
                ->first();

            if (!$sitExists) {
                // Yoksa => oluştur
                $sit = new WorkmanagerSituation();
                $sit->user_id = $user->id;
                $sit->active_hours = null;
                $sit->is_active = 1;    // sabah saatlerinde aktif
                $sit->location_info = null;
                $sit->save();
            }
        }

        $this->info("AutoCreateLogs => tamamlandı!");
        return 0;
    }

    /**
     * Kullanıcı bugün off mu? (Hafta sonu, tatil, izin vs.)
     * dailyCheck fonksiyonundaki mantığa benzer şekilde çalışır.
     */
    private function checkIfOff($userId, $today)
    {
        // 1) Hafta sonu mu?
        // 0=Pazar, 6=Cumartesi
        $dow = Carbon::parse($today)->dayOfWeek; 
        if ($dow == 0 || $dow == 6) {
            return true;
        }

        // 2) Tatil / resmi bayram tablosu
        // Holiday modelindeki start_date/end_date arasında mı?
        $holiday = Holiday::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();
        if ($holiday) {
            return true;
        }

        // 3) İzin / rapor (örnek HalfdayRequest tablosu)
        $izin = HalfdayRequest::where('user_id', $userId)
            ->where('date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->where('end_date', '>=', $today)
                  ->orWhereNull('end_date');
            })
            ->where('status', 'approved')
            ->first();

        if ($izin) {
            // Tam gün izin/rapor ise off kabul
            if ($izin->type == 'full_day' || $izin->type == 'rapor') {
                return true;
            }
            // Yarım gün sabah / öğleden sonra logic’i buradan eklenebilir
            // Şimdilik kısaca sabah ise sabah off / öğleden sonra off
        }

        return false;
    }
}
