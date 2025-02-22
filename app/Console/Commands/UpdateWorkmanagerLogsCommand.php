<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\User;
use App\Models\WorkmanagerLog;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;


class UpdateWorkmanagerLogsCommand extends Command implements ShouldQueue
{
    protected $signature = 'workmanager:updatelogs';
    protected $description = 'Workmanager AI job: Kullanıcıların anlık konumlarına, izin/rapor durumlarına ve mesai bilgilerine göre workmanager_logs tablosunu günceller.';

    public function handle()
    {
        $now = Carbon::now();
        $today = Carbon::today()->format('Y-m-d');
        $kernelLog = [];

        $this->info('Workmanager log update job başlatıldı: ' . $now->toDateTimeString());

        // 1) Tatil/Hafta Sonu Kontrolü
        if ($this->isHolidayOrWeekend()) {
            $this->info('Bugün tatil veya hafta sonu. İşlem devre dışı bırakıldı.');
            return;
        }

        // 2) workmanager_ai = true olan kullanıcıları alıyoruz
        $users = User::where('workmanager_ai', 'true')->get();
        if ($users->isEmpty()) {
            $this->info('İşlenecek kullanıcı bulunamadı.');
            return;
        }

        foreach ($users as $user) {
            $kernelLog[$user->id] = ['user' => $user->name];

            // 0) FCM Token Kontrolü: Token yoksa kullanıcı atlanır.
            $fcmTokens = DB::table('user_fcm_tokens')
                ->where('user_id', $user->id)
                ->pluck('fcm_token')
                ->toArray();
            if (empty($fcmTokens)) {
                $kernelLog[$user->id]['fcmToken'] = 'No FCM token found; user skipped';
                continue;
            }

            // 1) İzin/Rapor Kontrolü
            if ($this->isUserOnLeave($user)) {
                $kernelLog[$user->id]['leave'] = 'User on approved leave/rapor, processing skipped';
                continue;
            }

            // 2) Özel Saat Kontrolü (user_hours)
            if ($this->hasCustomHours($user)) {
                $kernelLog[$user->id]['customHours'] = 'User has custom hours; workmanager skipped';
                continue;
            }

            // 3) Mevcut workmanager_logs kaydını getiriyoruz (yeni kayıt oluşturulmaz)
            $wmLog = WorkmanagerLog::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();
            if (!$wmLog) {
                $kernelLog[$user->id]['wmLog'] = 'Log kaydı bulunamadı, atlandı';
                continue;
            }

            // 4) Geo Log Kontrolü: Kullanıcının anlık konumunu alıyoruz
            $lastGeoLog = DB::table('geo_logs')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();
            if (!$lastGeoLog) {
                $kernelLog[$user->id]['geoLog'] = 'Geo log bulunamadı';
                continue;
            }
            // Eğer geo_logs'ta "location" sütunu yoksa, lat & lng birleşimi kullanılır.
            $currentLocation = isset($lastGeoLog->location)
                ? $lastGeoLog->location
                : ($lastGeoLog->lat . ',' . $lastGeoLog->lng);

            // 5) Kullanıcının aktifliği: Son geo log kaydı 10 saniye içinde alınmışsa aktif kabul edilir.
            $isActive = (Carbon::parse($lastGeoLog->created_at)->diffInSeconds($now) <= 40);
            $kernelLog[$user->id]['active'] = $isActive ? 'active' : 'inactive';
            if (!$isActive) {
                $kernelLog[$user->id]['action'] = 'User inactive; resume command should be triggered';
                // Resume push gönderildikten sonra 5 dakika içinde kontrol edilecek.
                $this->checkWorkManagerResumeStatus($user);
                continue;
            }

            // Kullanıcının bugünkü check-in (attendance) kaydı alınıyor.
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('check_in_time', $today)
                ->first();

            // --- SABAH İŞLEMLERİ ---
            // a) 06:00-08:00 arası: "Yaklaştınız" bildirimi
            if ($now->between(Carbon::createFromTime(6, 0, 0), Carbon::createFromTime(8, 0, 0))) {
                if (!$attendance) {
                    if ($this->isWithinProximity($currentLocation, $user->check_in_location)) {
                        $this->sendEarlyCheckInNotification($user);
                        $kernelLog[$user->id]['earlyPush'] = 'Early check-in push sent';
                    } else {
                        $kernelLog[$user->id]['earlyPush'] = 'User not near check-in location (06:00-08:00)';
                    }
                } else {
                    $kernelLog[$user->id]['earlyPush'] = 'User already checked in';
                }
            }

            // b) 08:00-09:00 arası: Giriş kontrolleri (checkGiris09, checkGiris11, checkGiris12_20)
            if (!$attendance && $now->hour >= 8 && $now->hour < 9) {
                if ($this->isWithinProximity($currentLocation, $user->check_in_location)) {
                    if ($now->hour == 9 && $wmLog->checkGiris09 == 0) {
                        $wmLog->checkGiris09 = 1;
                        $wmLog->save();
                        $kernelLog[$user->id]['checkGiris09'] = 'Updated at 09:00';
                    }
                    if ($now->hour == 11 && $wmLog->checkGiris11 == 0) {
                        $wmLog->checkGiris11 = 1;
                        $wmLog->save();
                        $kernelLog[$user->id]['checkGiris11'] = 'Updated at 11:00';
                    }
                    if ($now->between(Carbon::createFromTime(12, 20, 0), Carbon::createFromTime(12, 21, 0)) && $wmLog->checkGiris12_20 == 0) {
                        $wmLog->checkGiris12_20 = 1;
                        $wmLog->save();
                        $kernelLog[$user->id]['checkGiris12_20'] = 'Updated at 12:20';
                    }
                } else {
                    $kernelLog[$user->id]['morningLocation'] = 'User not near check-in location';
                }
            } else {
                $kernelLog[$user->id]['morning'] = 'User already checked in or not in early morning period';
            }

            // --- AKŞAM İŞLEMLERİ ---
            // Eğer kullanıcı shift durumunda ise, akşam bildirimleri gönderilmez.
            if ($this->isUserInShift($user)) {
                $kernelLog[$user->id]['evening'] = 'User in shift; evening notifications deferred';
            } else {
                // Normal mesai yapan kullanıcı için:
                if ($attendance && !$attendance->check_out_time) {
                    if ($this->isWithinProximity($currentLocation, $user->check_out_location)) {
                        if ($now->hour == 16 && $now->minute >= 50 && $wmLog->checkCikis1655 == 0) {
                            $wmLog->checkCikis1655 = 1;
                            $wmLog->save();
                            $kernelLog[$user->id]['checkCikis1655'] = 'Updated at 16:50';
                        }
                        if ($now->hour == 17 && $now->minute >= 10 && $wmLog->checkCikis1715 == 0) {
                            $wmLog->checkCikis1715 = 1;
                            $wmLog->save();
                            $kernelLog[$user->id]['checkCikis1715'] = 'Updated at 17:10';
                        }
                        if ($now->hour >= 17 && $now->minute >= 40 && $wmLog->checkCikisAfter1740 == 0) {
                            $wmLog->checkCikisAfter1740 = 1;
                            $wmLog->save();
                            $kernelLog[$user->id]['checkCikisAfter1740'] = 'Updated at 17:40';
                        }
                    } else {
                        $kernelLog[$user->id]['eveningLocation'] = 'User not near check-out location';
                    }
                } else {
                    if ($attendance) {
                        // Normal mesai yapan kullanıcılar: eğer 18:00'den sonra çıkış yapılmamışsa WorkManager stop tetiklenecek.
                        if ($now->hour >= 18) {
                            $kernelLog[$user->id]['stop'] = 'User will be stopped at 18:00';
                            // 18:00'de gönderilen stop push'ı sonrası, 18:05'te geo_logs kontrolü yapılacak.
                            $this->checkWorkManagerStopStatus($user);
                        } else {
                            $kernelLog[$user->id]['evening'] = 'User already checked out or waiting for stop trigger';
                        }
                    } else {
                        $kernelLog[$user->id]['evening'] = 'User did not check in; evening processing skipped';
                    }
                }
            }
        } // foreach sonu

        // Günlük kernel loglarını JSON dosyası haline getiriyoruz.
        $jsonData = json_encode($kernelLog, JSON_PRETTY_PRINT);
        $filename = 'workmanager_kernel_' . $today . '.json';
        Storage::disk('local')->put($filename, $jsonData);

        // *** ADMIN BİLDİRİM MEKANİZMASI ***
        $admin = User::where('role', 1)->first();
        if ($admin) {
            // Sabah bildirimi: 08:00'de, WorkManager aktif mesai kullanıcı sayısı bildirilsin.
            if ($now->hour == 8) {
                // İsteğe göre sadece mesai (shift dışı) kullanıcılar sayılabilir.
                $mesaiCount = 0;
                foreach ($kernelLog as $log) {
                    // Örneğin, 'evening' veya 'stop' anahtarı yoksa kullanıcı aktif kabul edilebilir.
                    if (!isset($log['evening']) && !isset($log['stop'])) {
                        $mesaiCount++;
                    }
                }
                FCMHelper::sendNotification($admin, 'WorkManager Resume: ' . $mesaiCount . ' mesai kullanıcısı aktif.');
            }
            // Gece bildirimi: 00:00'da, WorkManager stop edilen mesai kullanıcı sayısı bildirilsin.
            if ($now->hour == 0) {
                $stopCount = 0;
                foreach ($kernelLog as $log) {
                    if (isset($log['stop'])) {
                        $stopCount++;
                    }
                }
                FCMHelper::sendNotification($admin, 'WorkManager Stop: ' . $stopCount . ' mesai kullanıcısı durdu.');
            }
        }
        // *** ADMIN BİLDİRİM MEKANİZMASI SONU ***

        $this->info('Workmanager logs update job tamamlandı.');
    }

    /**
     * İki konum arasındaki yakınlığı kontrol eder.
     * @param string $currentLocation (örn. "37.13318,38.74039")
     * @param string $designatedLocation (örn. "37.132864,38.740704")
     * @param float $threshold (varsayılan 0.001)
     * @return bool
     */
    private function isWithinProximity($currentLocation, $designatedLocation, $threshold = 0.001)
    {
        list($currLat, $currLng) = explode(',', $currentLocation);
        list($desLat, $desLng) = explode(',', $designatedLocation);
        $latDiff = abs(floatval($currLat) - floatval($desLat));
        $lngDiff = abs(floatval($currLng) - floatval($desLng));
        return ($latDiff < $threshold && $lngDiff < $threshold);
    }

    /**
     * Bugünün tatil/hafta sonu olup olmadığını kontrol eder.
     * @return bool
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
     * Kullanıcının bugün izinli veya raporlu olup olmadığını kontrol eder.
     * @param User $user
     * @return bool
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
     * Kullanıcının shift (mesai) durumunu shift_logs tablosundan kontrol eder.
     * @param User $user
     * @return bool
     */
    private function isUserInShift($user)
    {
        $today = Carbon::today()->toDateString();
        $shift = DB::table('shift_logs')
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
        return $shift ? true : false;
    }

    /**
     * Sabah erken saatlerde (06:00-08:00) kullanıcının check_in_location'a yaklaştığını tespit ederse,
     * kullanıcıya "yaklaştınız" push bildirimi gönderir.
     * @param User $user
     */
    private function sendEarlyCheckInNotification($user)
    {
        $factory = (new \Kreait\Firebase\Factory)
            ->withServiceAccount(config('services.firebase.credentials_file'));
        $messaging = $factory->createMessaging();

        \Log::info("[sendEarlyCheckInNotification] => User ID: {$user->id} için erken giriş bildirimi gönderiliyor.");

        $tokens = DB::table('user_fcm_tokens')
            ->where('user_id', $user->id)
            ->pluck('fcm_token')
            ->toArray();

        if (empty($tokens)) {
            \Log::info("[sendEarlyCheckInNotification] => User ID: {$user->id} için token bulunamadı.");
            return;
        }

        $message = \Kreait\Firebase\Messaging\CloudMessage::new()
            ->withNotification([
                'title' => 'Yaklaştınız',
                'body' => 'Belirlenen giriş noktanıza yaklaştınız, lütfen giriş yapınız.',
            ])
            ->withData([
                'action' => 'early_check_in',
                'user_id' => (string) $user->id,
            ]);

        $sendReport = $messaging->sendMulticast($message, $tokens);
        $successCount = $sendReport->successes()->count();
        $failureCount = $sendReport->failures()->count();
        \Log::info("[sendEarlyCheckInNotification] => User ID: {$user->id} için push gönderimi: success=$successCount, failure=$failureCount");
    }

    /**
     * Çalışan kullanıcıların WorkManager resume durumunu kontrol eder.
     * Eğer resume push gönderildikten sonra 5 dakika içinde geo_logs'ta yeni bir kayıt alınmamışsa,
     * WorkManager resume işleminin gerçekleşmediğini loglar ve yeniden resume bildirimi gönderir.
     * @param User $user
     */
    private function checkWorkManagerResumeStatus($user)
    {
        // Beklenen kontrol süresi: resume push gönderildikten 5 dakika (300 saniye) sonra.
        $now = Carbon::now();

        // En son geo_log kaydını alıyoruz.
        $lastGeoLog = DB::table('geo_logs')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastGeoLog) {
            $lastLogTime = Carbon::parse($lastGeoLog->created_at);
            // Eğer son geo_log kaydı 5 dakika (300 saniye) içinde alınmışsa, resume başarılı kabul ediliyor.
            if ($lastLogTime->diffInSeconds($now) <= 300) {
                \Log::info("[checkWorkManagerResumeStatus] => User ID: {$user->id} için WorkManager resume başarılı. (Son geo log: {$lastLogTime->toDateTimeString()})");
            } else {
                \Log::warning("[checkWorkManagerResumeStatus] => User ID: {$user->id} için WorkManager resume başarısız, yeniden denenecek. (Son geo log: {$lastLogTime->toDateTimeString()})");
                $this->triggerWorkManagerResume($user);
            }
        } else {
            \Log::warning("[checkWorkManagerResumeStatus] => User ID: {$user->id} için geo log bulunamadı, resume kontrolü yapılamadı.");
        }
    }

    /**
     * Resume push bildirimi gönderme işlemlerini tetikler.
     * @param User $user
     */
    private function triggerWorkManagerResume($user)
    {
        \Log::info("[triggerWorkManagerResume] => User ID: {$user->id} için yeniden resume bildirimi gönderiliyor.");
        // Örneğin, FCM üzerinden resume bildirimi gönderilebilir:
        FCMHelper::sendNotification($user, 'WorkManager Resume: Lütfen durumunuzu kontrol ediniz.');
    }

    /**
     * Çalışan kullanıcıların WorkManager stop durumunu kontrol eder.
     * Eğer 18:00'de gönderilen stop push'ı sonrası, beklenen kontrol zamanı (18:05) sonrasında,
     * geo_logs'ta güncel kayıt alınmamışsa WorkManager stop işleminin gerçekleşmediğini loglar; 
     * aksi halde stop başarılı kabul edilir. 
     * Eğer başarısız ise stop işlemi yeniden tetiklenir.
     * @param User $user
     */
    private function checkWorkManagerStopStatus($user)
    {
        // Beklenen kontrol zamanı: 18:05
        $expectedCheckTime = Carbon::createFromTime(18, 5, 0);
        $now = Carbon::now();
        if ($now->lessThan($expectedCheckTime)) {
            \Log::info("[checkWorkManagerStopStatus] => User ID: {$user->id} için kontrol zamanı henüz gelmedi.");
            return;
        }

        $lastGeoLog = DB::table('geo_logs')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastGeoLog) {
            $lastLogTime = Carbon::parse($lastGeoLog->created_at);
            if ($lastLogTime->lessThan($expectedCheckTime)) {
                \Log::info("[checkWorkManagerStopStatus] => User ID: {$user->id} için WorkManager stop başarılı. (Son güncelleme: {$lastLogTime->toDateTimeString()})");
            } else {
                \Log::warning("[checkWorkManagerStopStatus] => User ID: {$user->id} için WorkManager stop başarısız, yeniden denenecek. (Son güncelleme: {$lastLogTime->toDateTimeString()})");
                // Eğer stop başarısız ise, stop işlemini yeniden tetikleyelim.
                $this->triggerWorkManagerStop($user);
            }
        } else {
            \Log::warning("[checkWorkManagerStopStatus] => User ID: {$user->id} için geo_log bulunamadı, stop kontrolü yapılamadı.");
        }
    }

    /**
     * Stop push bildirimi gönderme işlemlerini tetikler.
     * @param User $user
     */
    private function triggerWorkManagerStop($user)
    {
        \Log::info("[triggerWorkManagerStop] => User ID: {$user->id} için yeniden stop bildirimi gönderiliyor.");
        // Örneğin, FCM üzerinden stop bildirimi gönderilebilir:
        FCMHelper::sendNotification($user, 'WorkManager Stop: Lütfen durumu kontrol ediniz.');
    }

    /**
     * Kullanıcının özel saat tanımlı olup olmadığını user_hours tablosundan kontrol eder.
     * @param User $user
     * @return bool
     */
    private function hasCustomHours($user)
    {
        $record = DB::table('user_hours')
            ->where('user_id', $user->id)
            ->first();
        return $record ? true : false;
    }
}
