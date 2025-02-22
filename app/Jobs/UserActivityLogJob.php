<?php
namespace App\Jobs;

use App\Models\GeoLog;
use App\Models\User;
use App\Models\UserActivityLog;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UserActivityLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $now = Carbon::now();

        // Saat aralığı kontrolü (07:00 - 18:00)
        if (!$now->between(Carbon::createFromTime(7, 0), Carbon::createFromTime(18, 0))) {
            return;
        }

        // Haftasonu veya tatil kontrolü
        if ($this->isHolidayOrWeekend()) {
            return;
        }

        // Tüm kullanıcıların son geo_logs kaydını al
        $users = GeoLog::select('user_id')->groupBy('user_id')->get();

        foreach ($users as $geoLog) {
            $user = User::find($geoLog->user_id);
            if (!$user || $this->isUserOnLeave($user) || $this->hasCustomHours($user)) {
                continue;
            }

            $lastGeoLog = GeoLog::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastGeoLog) {
                $status = $this->determineUserStatus($user, $lastGeoLog, $now);

                // Kullanıcı verisini güncelle
                UserActivityLog::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'user_name'   => $user->name,
                        'last_lat'    => $lastGeoLog->lat,
                        'last_lng'    => $lastGeoLog->lng,
                        'status'      => $status,
                        'event_time'  => $now
                    ]
                );
            }
        }
    }

    /**
     * Konum kontrolü yapar (sabah/akşam)
     */
    private function determineUserStatus($user, $geoLog, $now)
    {
        $morningStart = Carbon::createFromTime(8, 0);
        $noon = Carbon::createFromTime(12, 0);
        $evening = Carbon::createFromTime(17, 0);

        $currentLat = $geoLog->lat;
        $currentLng = $geoLog->lng;

        // Kullanıcının belirlenen giriş ve çıkış koordinatlarını al
        [$checkInLat, $checkInLng] = explode(',', $user->check_in_location);
        [$checkOutLat, $checkOutLng] = explode(',', $user->check_out_location);

        // Mesafe toleransı (yaklaşık 50 metre fark varsa)
        $tolerance = 0.0005;

        // Sabah kontrolü
        if ($now->between($morningStart, $noon)) {
            if ($this->isUserAtLocation($currentLat, $currentLng, $checkInLat, $checkInLng, $tolerance)) {
                return 'on_location';
            } else {
                return 'departed';
            }
        }

        // Akşam kontrolü
        if ($now->between($noon, $evening)) {
            if ($this->isUserAtLocation($currentLat, $currentLng, $checkOutLat, $checkOutLng, $tolerance)) {
                return 'returned';
            } else {
                return 'departed';
            }
        }

        // Anlık durum kontrolü: 10 dakika boyunca hareket yoksa
        $lastUpdate = Carbon::parse($geoLog->created_at);
        $timeDiff = $now->diffInMinutes($lastUpdate);
        return ($timeDiff > 10) ? 'inactive' : 'active';
    }

    /**
     * Konum karşılaştırması yapar
     */
    private function isUserAtLocation($currentLat, $currentLng, $targetLat, $targetLng, $tolerance)
    {
        return abs($currentLat - $targetLat) <= $tolerance &&
               abs($currentLng - $targetLng) <= $tolerance;
    }

    /**
     * Hafta sonu veya tatil kontrolü yapar.
     */
    private function isHolidayOrWeekend()
    {
        $today = Carbon::today();
        if (in_array($today->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
            return true;
        }
        $holiday = DB::table('holidays')
            ->where('start_date', '<=', $today->toDateString())
            ->where('end_date', '>=', $today->toDateString())
            ->first();
        return $holiday ? true : false;
    }

    /**
     * Kullanıcının izin veya raporlu olup olmadığını kontrol eder.
     */
    private function isUserOnLeave($user)
    {
        $today = Carbon::today()->toDateString();
        $leave = DB::table('halfday_requests')
            ->where('user_id', $user->id)
            ->where('date', $today)
            ->whereIn('type', ['morning', 'rapor'])
            ->where('status', 'approved')
            ->first();
        return $leave ? true : false;
    }

    /**
     * Kullanıcının özel saat tanımlaması olup olmadığını kontrol eder.
     */
    private function hasCustomHours($user)
    {
        $record = DB::table('user_hours')
            ->where('user_id', $user->id)
            ->first();
        return $record ? true : false;
    }
}
