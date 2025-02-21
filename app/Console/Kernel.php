<?php

namespace App\Console;

use App\Models\ManualNotification;
use App\Models\WorkmanagerLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\UserFcmToken;
use App\Models\Holiday; // Resmi tatil kontrolü
use App\Services\FcmService;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Factory as KreaitFactory;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        //
    ];

    /**
     * Laravel'in schedule tanımları.
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('notifications:check')->everyMinute(); //DATA BİLDİRİM

        $schedule->command('workmanager:check')->everyMinute(); //WORKMANAGER PUSH

        $schedule->command('auto:create-logs')->dailyAt('08:15'); //WORKMANAGER_LOGS TABLO OLUŞTURMA

        // Her dakika -> Bildirim tablosunu kontrol et
        $schedule->call(function () {
            $this->checkManualNotifications();
        })->everyFiveMinutes();
        
        // (test amaçlı)
        $schedule->call(function () {
            $this->sendPauseTokensAt(); //WORKMANAGER PAUSE
        })->dailyAt('23:19');

        $schedule->call(function () {
            $this->sendResumeTokensAt(); //WORKMANAGER RESUME
        })->dailyAt('23:01');

        // 06:30 => "Günaydın" (HAFTA İÇİ)
        $schedule->call(function () {
            $this->sendMorningGunaydin();
        })->weekdays()->at('15:37');

        // 09:00 => kim giriş yapmadı?
        $schedule->call(function () {
            $this->checkGiris09();
        })->weekdays()->at('18:07');

        // 11:00 => kim hâlâ girmedi?
        $schedule->call(function () {
            $this->checkGiris11();
        })->weekdays()->at('11:00');

        // 12:20 => giriş yapmayanlara "destek ekibi" uyarısı
        $schedule->call(function () {
            $this->checkGiris12_20();
        })->weekdays()->at('12:20');

        // 16:55 => çıkış uyarısı (giriş yaptı ama çıkmadı)
        $schedule->call(function () {
            $this->checkCikis1655();
        })->weekdays()->at('00:07');

        // 17:15 => son 5 dk
        $schedule->call(function () {
            $this->checkCikis1715();
        })->weekdays()->at('17:15');

        // 17:30 => kim hâlâ çıkmadı
        $schedule->call(function () {
            $this->checkCikisAfter1720();
        })->weekdays()->at('17:30');

        // 21:30 => hiç işlem yapmadıysa
        $schedule->call(function () {
            $this->checkNoRecords2130();
        })->weekdays()->at('21:30');
    }

    //////////
    // Bu metodu Kernel'de schedule() ile veya Artisan Command olarak kullanabilirsiniz:
    public function checkManualNotifications()
    {
        \Log::info("[checkManualNotifications] => started at " . now());

        $pendingNotifs = ManualNotification::where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->get();

        if ($pendingNotifs->isEmpty()) {
            \Log::info("[checkManualNotifications] => no pending notifications => done");
            return;
        }

        $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
        $messaging = $factory->createMessaging();

        foreach ($pendingNotifs as $notif) {
            \Log::info("[checkManualNotifications] => processing notif#{$notif->id}, title={$notif->title}");

            // **IF** => action == 'push' => normal notification
            if ($notif->action !== 'push') {
                \Log::info("[checkManualNotifications] => notif#{$notif->id} => action != push => skip");
                continue;
            }

            // 2A) Tokenları toplayacağımız dizi
            $allTokens = [];

            if ($notif->target_type === 'all') {
                $allTokens = UserFcmToken::pluck('fcm_token')->toArray();
            } elseif ($notif->target_type === 'user') {
                if ($notif->user_id) {
                    $tokens = UserFcmToken::where('user_id', $notif->user_id)
                        ->pluck('fcm_token')
                        ->toArray();
                    $allTokens = array_merge($allTokens, $tokens);
                }
            } elseif ($notif->target_type === 'group') {
                if ($notif->user_id) {
                    $ids = explode(',', $notif->user_id);
                    $ids = array_map('trim', $ids);
                    $tokens = UserFcmToken::whereIn('user_id', $ids)
                        ->pluck('fcm_token')
                        ->toArray();
                    $allTokens = array_merge($allTokens, $tokens);
                }
            }

            $allTokens = array_unique(array_filter($allTokens));
            if (empty($allTokens)) {
                \Log::warning("[checkManualNotifications] => notif#{$notif->id} => no tokens => mark failed");
                $notif->status = 'failed';
                $notif->save();
                continue;
            }

            \Log::info("[checkManualNotifications] => notif#{$notif->id}, tokenCount=" . count($allTokens));

            // 3) CloudMessage ile bildirim objesi
            $title = $notif->title;
            $body = $notif->body ?? '';

            $message = CloudMessage::new()
                ->withNotification([
                    'title' => $title,
                    'body' => $body,
                ]);

            // 4) sendMulticast => normal push
            $report = $messaging->sendMulticast($message, $allTokens);
            $successCount = $report->successes()->count();
            $failureCount = $report->failures()->count();

            \Log::info("[checkManualNotifications] => notif#{$notif->id} => success=$successCount, failure=$failureCount");

            if ($successCount > 0) {
                $notif->status = 'sent';
                $notif->sent_at = now();
            } else {
                $notif->status = 'failed';
            }
            $notif->save();

            // Hata log
            foreach ($report->failures() as $fail) {
                $err = $fail->rawError();
                \Log::warning("[checkManualNotifications] => notif#{$notif->id} => error=" . json_encode($err));
            }
        }

        \Log::info("[checkManualNotifications] => done");
    }


    public function sendResumeTokensAt()
    {
        // 1) Firebase Messaging yapılandırması
        $factory = (new KreaitFactory)
            ->withServiceAccount(config('services.firebase.credentials_file'));
        $messaging = $factory->createMessaging();

        Log::info("[sendResumeTokensAt2310] Tetiklendi - Tüm tokenlara push gönderilecek.");

        // 2) Veritabanındaki tüm tokenları alıyoruz
        $allTokens = UserFcmToken::pluck('fcm_token')->toArray();
        if (empty($allTokens)) {
            Log::info("[sendResumeTokensAt2310] Veritabanında hiç token yok!");
            return;
        }
        Log::info("[sendResumeTokensAt2310] Token sayısı: " . count($allTokens));

        // 3) Bildirim ve data payload hazırlıyoruz  
        // Örneğin; WorkManager ve BFS'yi yeniden başlatmak için "resume" komutu gönderiyoruz.
        $msg = CloudMessage::new()
            ->withNotification([
                'title' => 'WorkManager Komut',
                'body' => 'WorkManager yeniden başlatma komutu gönderildi.',
            ])
            ->withData([
                'action' => 'resume',      // Komut resume
                'user_id' => '1',          // İlgili kullanıcı ID'si (gerektiği gibi ayarlanabilir)
            ]);

        // 4) Çoklu tokena tek seferde push gönderimi
        $sendReport = $messaging->sendMulticast($msg, $allTokens);

        $successCount = $sendReport->successes()->count();
        $failureCount = $sendReport->failures()->count();
        Log::info("[sendResumeTokensAt2310] Success: $successCount, Failure: $failureCount");

        // Hatalı tokenları logla
        foreach ($sendReport->failures() as $failure) {
            $error = $failure->rawError();
            Log::warning("[sendResumeTokensAt2310] Hatalı token: " . json_encode($error));
        }
        Log::info("[sendResumeTokensAt2310] İşlem tamamlandı.");
    }

    ////
    /**
     * Tüm tokenlara tek seferde push örneği (sendMulticast)
     */
    public function sendPauseTokensAt()
    {
        // 1) Firebase Messaging yapılandırması
        $factory = (new KreaitFactory)
            ->withServiceAccount(config('services.firebase.credentials_file'));
        $messaging = $factory->createMessaging();

        Log::info("[sendAllTokensAt2310] Tetiklendi - Tüm tokenlara push gönderilecek.");

        // 2) Veritabanındaki tüm tokenları alıyoruz
        $allTokens = UserFcmToken::pluck('fcm_token')->toArray();
        if (empty($allTokens)) {
            Log::info("[sendAllTokensAt2310] Veritabanında hiç token yok!");
            return;
        }
        Log::info("[sendAllTokensAt2310] Token sayısı: " . count($allTokens));

        // 3) Bildirim ve data payload hazırlıyoruz  
        // Örneğin; WorkManager’ı 60 dakika durdurmak için "pause" komutu gönderiyoruz.
        $msg = CloudMessage::new()
            ->withNotification([
                'title' => 'WorkManager Komut',
                'body' => 'WorkManager durdurma komutu gönderildi.',
            ])
            ->withData([
                'action' => 'pause',      // İstediğiniz komut "pause" veya "resume"
                'duration' => '5',         // Durma süresi (dakika)
                'user_id' => '1',          // Gerekirse ilgili kullanıcı ID’si
            ]);

        // 4) Çoklu tokena tek seferde push gönderimi
        $sendReport = $messaging->sendMulticast($msg, $allTokens);

        $successCount = $sendReport->successes()->count();
        $failureCount = $sendReport->failures()->count();
        Log::info("[sendAllTokensAt2310] Success: $successCount, Failure: $failureCount");

        foreach ($sendReport->failures() as $failure) {
            $error = $failure->rawError();
            Log::warning("[sendAllTokensAt2310] Hatalı token: " . json_encode($error));
        }

        Log::info("[sendAllTokensAt2310] İşlem tamamlandı.");
    }

    /**
     * 06:30 => "Günaydın" (Tüm user'lara, user_fcm_tokens aracılığıyla)
     */

    protected function sendMorningGunaydin()
    {
        // 0) WorkManager Logs kontrolü
        $today = Carbon::today()->format('Y-m-d');
        $wmLogs = WorkmanagerLog::whereDate('date', $today)
            ->where('sendMorningGunaydin', 1)
            ->get();

        if (!$wmLogs->isEmpty()) {
            \Log::info("[sendMorningGunaydin] => workmanager_logs => " . count($wmLogs) . " adet => sendMorningGunaydin=1 => FCM gonderiyor (WM logic)");

            $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
            $messaging = $factory->createMessaging();

            foreach ($wmLogs as $logRow) {
                $userId = $logRow->user_id;

                // 1) Bu user'ın tokenları
                $tokens = UserFcmToken::where('user_id', $userId)->pluck('fcm_token')->toArray();
                if (empty($tokens)) {
                    \Log::info("[sendMorningGunaydin] => WM => user_id=$userId => token yok, skip");
                    continue;
                }

                // 2) Bildirim içeriği
                $title = "Günaydın (WorkManager)";
                $body = "Mesaiye Başlamak İçin Hazır mısınız? (workmanager_logs=1)";
                $message = CloudMessage::new()->withNotification([
                    'title' => $title,
                    'body' => $body,
                ]);

                // 3) Gönder
                $report = $messaging->sendMulticast($message, $tokens);
                $success = $report->successes()->count();
                $fail = $report->failures()->count();

                \Log::info("[sendMorningGunaydin] => WM => user_id=$userId => success=$success, fail=$fail");

                // 4) Sütunu 2 yap => tekrar tetiklenmesin
                $logRow->sendMorningGunaydin = 2;
                $logRow->save();
            }
        } else {
            \Log::info("[sendMorningGunaydin] => WM => sendMorningGunaydin=1 yok, devam ediyorum...");
        }

        // A) Tatil/Hafta sonu engeli
        if ($this->isHolidayOrWeekend()) {
            \Log::info("[sendMorningGunaydin] => tatil/hafta sonu => iptal");
            return;
        }

        \Log::info("[sendMorningGunaydin] => fonksiyon tetiklendi, now=" . now());

        // B) (Mevcut kod) => Tüm user'lara "Günaydın" atma
        // 1) fcm_role='yes' olan tüm kullanıcılar
        $users = User::where('fcm_role', 'yes')->get();
        if ($users->isEmpty()) {
            \Log::info("[sendMorningGunaydin] => hic user yok => iptal");
            return;
        }
        \Log::info("[sendMorningGunaydin] => user count=" . $users->count());

        // 2) Tüm tokenları tek array'de toplayalım
        $allTokens = [];
        foreach ($users as $u) {
            $tokens = UserFcmToken::where('user_id', $u->id)
                ->pluck('fcm_token')
                ->toArray();

            if (!empty($tokens)) {
                $allTokens = array_merge($allTokens, $tokens);
            }
        }

        \Log::info("[sendMorningGunaydin] => toplu token sayisi=" . count($allTokens));

        // Eger hic token yoksa push atmadan iptal edebiliriz
        if (empty($allTokens)) {
            \Log::info("[sendMorningGunaydin] => hic token yok => push yok");
            return;
        }

        // 3) Kreait => sendMulticast
        $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
        $messaging = $factory->createMessaging();

        $message = CloudMessage::new()->withNotification([
            'title' => 'Günaydın',
            'body' => 'Mesaiye Başlamak İçin Hazır mısınız?',
        ]);

        // Tek seferde çoklu tokena push
        $sendReport = $messaging->sendMulticast($message, $allTokens);

        // 4) Log sonuç
        $successCount = $sendReport->successes()->count();
        $failureCount = $sendReport->failures()->count();
        \Log::info("[sendMorningGunaydin] => success=$successCount, failure=$failureCount");

        foreach ($sendReport->failures() as $fail) {
            $error = $fail->rawError();
            \Log::warning("[sendMorningGunaydin] => hata=" . json_encode($error));
        }

        \Log::info("[sendMorningGunaydin] => islem bitti.");
    }



    /**
     * 09:00 => kim giriş yapmadı
     */
    protected function checkGiris09()
    {
        // 0) ÖNCE WorkManager Logs => checkGiris09=1
        \Log::info("[checkGiris09] => tetiklendi, now=" . now());
        $todayDate = Carbon::today()->format('Y-m-d');

        // A) workmanager_logs tablosunda => bugünün kaydı + checkGiris09=1
        $wmLogs = WorkmanagerLog::whereDate('date', $todayDate)
            ->where('checkGiris09', 1)
            ->get();

        if (!$wmLogs->isEmpty()) {
            \Log::info("[checkGiris09] => WM => " . count($wmLogs) . " adet log => FCM gonderilecek");
            // Firebase init
            $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
            $messaging = $factory->createMessaging();

            // Her log satırı => user_id => FCM gönder => sütun=2
            foreach ($wmLogs as $log) {
                $uId = $log->user_id;
                $tokens = UserFcmToken::where('user_id', $uId)->pluck('fcm_token')->toArray();

                if (empty($tokens)) {
                    \Log::info("[checkGiris09] => WM => user_id=$uId => token yok => skip");
                    continue;
                }

                // Bildirim
                $title = "Giriş Hatırlatma (WorkManager)";
                $body = "Saat 09:00 => Hâlâ giriş yapmadınız! (workmanager_logs=1)";

                $message = CloudMessage::new()
                    ->withNotification([
                        'title' => $title,
                        'body' => $body,
                    ]);

                $report = $messaging->sendMulticast($message, $tokens);
                $success = $report->successes()->count();
                $fail = $report->failures()->count();

                \Log::info("[checkGiris09] => WM => user_id=$uId => success=$success, fail=$fail");

                // Sütunu 2 yap => tekrar tetiklenmesin
                $log->checkGiris09 = 2;
                $log->save();
            }
        } else {
            \Log::info("[checkGiris09] => WM => hic log yok (checkGiris09=1)");
        }

        // 1) Tüm user => fcm_role='yes'
        $today = Carbon::today();
        $users = User::where('fcm_role', 'yes')->get();
        if ($users->isEmpty()) {
            \Log::info("[checkGiris09] => hic user yok => iptal");
            return;
        }
        \Log::info("[checkGiris09] => total user count=" . $users->count());

        // 2) Giriş yapmayan kullanıcıların tüm tokenlarını tek bir arrayde toplayacağız
        $allTokens = []; // bu array, bugun giris yapmayan TUM user'larin tokenlarini birleştirecek
        $noAttendanceCount = 0;
        $alreadyCheckInCount = 0;

        foreach ($users as $user) {
            // attendances tablosunda bugünün kaydı var mı?
            $count = Attendance::where('user_id', $user->id)
                ->whereDate('check_in_time', $today)
                ->count();

            if ($count == 0) {
                // => bu user hiç giriş yapmamış
                $noAttendanceCount++;

                // Bu user'ın tum token'larını bul
                $tokens = UserFcmToken::where('user_id', $user->id)
                    ->pluck('fcm_token')
                    ->toArray();

                // Tokenları $allTokens dizisine ekle
                if (!empty($tokens)) {
                    $allTokens = array_merge($allTokens, $tokens);
                }
            } else {
                // bu user zaten giriş yapmış
                $alreadyCheckInCount++;
            }
        }

        \Log::info("[checkGiris09] => noAttendanceCount=$noAttendanceCount, alreadyCheckInCount=$alreadyCheckInCount");
        \Log::info("[checkGiris09] => toplanan token sayisi=" . count($allTokens));

        // 3) Eger hic token yoksa => kimseye push atmayacağız
        if (empty($allTokens)) {
            \Log::info("[checkGiris09] => hic token yok => push atilmiyor");
            return;
        }

        // 4) Kreait ile sendMulticast
        $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
        $messaging = $factory->createMessaging();

        // Bildirim içeriğini hazirla
        $message = CloudMessage::new()->withNotification([
            'title' => 'Giriş Yok',
            'body' => 'Saat 09:00 oldu, hâlâ giriş yapmadınız!',
        ]);

        // Toplu gönderim
        $sendReport = $messaging->sendMulticast($message, $allTokens);

        $successCount = $sendReport->successes()->count();
        $failureCount = $sendReport->failures()->count();

        // Log
        \Log::info("[checkGiris09] => success=$successCount, failure=$failureCount");

        // Hata detayları
        foreach ($sendReport->failures() as $fail) {
            $error = $fail->rawError();
            \Log::warning("[checkGiris09] => error=" . json_encode($error));
        }

        \Log::info("[checkGiris09] => islem bitti.");
    }


    /**
     * 11:00 => kim hâlâ girmedi
     */
    protected function checkGiris11()
    {
        // 0) ÖNCE WorkManager tablosu => checkGiris11=1
        \Log::info("[checkGiris11] => tetiklendi, now=" . now());
        $todayDate = Carbon::today()->format('Y-m-d');

        // A) workmanager_logs => bugünün kaydı + checkGiris11=1
        $wmLogs = WorkmanagerLog::whereDate('date', $todayDate)
            ->where('checkGiris11', 1)
            ->get();

        if (!$wmLogs->isEmpty()) {
            \Log::info("[checkGiris11] => WM => " . count($wmLogs) . " adet => checkGiris11=1 => FCM gonderiyor");
            // Firebase Messaging
            $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
            $messaging = $factory->createMessaging();

            foreach ($wmLogs as $logRow) {
                $userId = $logRow->user_id;
                $tokens = UserFcmToken::where('user_id', $userId)->pluck('fcm_token')->toArray();
                if (empty($tokens)) {
                    \Log::info("[checkGiris11] => WM => user_id=$userId => token yok => skip");
                    continue;
                }

                // Bildirim
                $title = "Giriş Hatırlatma (WorkManager)";
                $body = "Saat 11:00 => Hâlâ giriş yapmadınız! (workmanager_logs=1)";
                $message = CloudMessage::new()->withNotification([
                    'title' => $title,
                    'body' => $body,
                ]);

                $report = $messaging->sendMulticast($message, $tokens);
                $success = $report->successes()->count();
                $fail = $report->failures()->count();

                \Log::info("[checkGiris11] => WM => user_id=$userId => success=$success, fail=$fail");

                // Sütunu 2 yap => tekrar tetiklenmesin
                $logRow->checkGiris11 = 2;
                $logRow->save();
            }
        } else {
            \Log::info("[checkGiris11] => WM => checkGiris11=1 kayit yok, devam ediyorum...");
        }

        // 1) Tüm user => fcm_role='yes'
        $today = Carbon::today();
        $users = User::where('fcm_role', 'yes')->get();
        if ($users->isEmpty()) {
            \Log::info("[checkGiris11] => hic user yok => iptal");
            return;
        }
        \Log::info("[checkGiris11] => total user count=" . $users->count());

        // 2) Giriş yapmayan kullanıcıların tüm tokenlarını tek bir arrayde toplayacağız
        $allTokens = []; // bu array, bugun giris yapmayan TUM user'larin tokenlarini birleştirecek
        $noAttendanceCount = 0;
        $alreadyCheckInCount = 0;

        foreach ($users as $user) {
            // attendances tablosunda bugünün kaydı var mı?
            $count = Attendance::where('user_id', $user->id)
                ->whereDate('check_in_time', $today)
                ->count();

            if ($count == 0) {
                // => bu user hiç giriş yapmamış
                $noAttendanceCount++;

                // Bu user'ın tum token'larını bul
                $tokens = UserFcmToken::where('user_id', $user->id)
                    ->pluck('fcm_token')
                    ->toArray();

                // Tokenları $allTokens dizisine ekle
                if (!empty($tokens)) {
                    $allTokens = array_merge($allTokens, $tokens);
                }
            } else {
                // bu user zaten giriş yapmış
                $alreadyCheckInCount++;
            }
        }

        \Log::info("[checkGiris11] => noAttendanceCount=$noAttendanceCount, alreadyCheckInCount=$alreadyCheckInCount");
        \Log::info("[checkGiris11] => toplanan token sayisi=" . count($allTokens));

        // 3) Eger hic token yoksa => kimseye push atmayacağız
        if (empty($allTokens)) {
            \Log::info("[checkGiris11] => hic token yok => push atilmiyor");
            return;
        }

        // 4) Kreait ile sendMulticast
        $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
        $messaging = $factory->createMessaging();

        // Bildirim içeriğini hazirla
        $message = CloudMessage::new()->withNotification([
            'title' => 'Giriş Hatırlatma',
            'body' => 'Saat 11:00 oldu, hâlâ giriş yapmadınız!',
        ]);

        // Toplu gönderim
        $sendReport = $messaging->sendMulticast($message, $allTokens);

        $successCount = $sendReport->successes()->count();
        $failureCount = $sendReport->failures()->count();

        // Log
        \Log::info("[checkGiris11] => success=$successCount, failure=$failureCount");

        // Hata detayları
        foreach ($sendReport->failures() as $fail) {
            $error = $fail->rawError();
            \Log::warning("[checkGiris11] => error=" . json_encode($error));
        }

        \Log::info("[checkGiris11] => islem bitti.");
    }


    /**
     * 12:20 => Giriş yapmayanlara "Destek ekibiyle görüşün"
     */
    protected function checkGiris12_20()
    {
        \Log::info("[checkGiris12_20] => tetiklendi, now=" . now());

        // 0) ÖNCE workmanager_logs => checkGiris12_20=1
        $todayDate = Carbon::today()->format('Y-m-d');

        $wmLogs = WorkmanagerLog::whereDate('date', $todayDate)
            ->where('checkGiris12_20', 1)
            ->get();

        if (!$wmLogs->isEmpty()) {
            \Log::info("[checkGiris12_20] => WM => " . count($wmLogs) . " adet => checkGiris12_20=1 => FCM gonder");
            $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
            $messaging = $factory->createMessaging();

            foreach ($wmLogs as $logRow) {
                $userId = $logRow->user_id;
                $tokens = UserFcmToken::where('user_id', $userId)->pluck('fcm_token')->toArray();
                if (empty($tokens)) {
                    \Log::info("[checkGiris12_20] => WM => user_id=$userId => token yok => skip");
                    continue;
                }

                // Bildirim içeriği
                $title = "Giriş Yapmadınız";
                $body = "Saat 12:20 => Artık çok geç, lütfen destek ekibiyle görüşün.";
                $cmsg = CloudMessage::new()->withNotification([
                    'title' => $title,
                    'body' => $body,
                ]);

                // Gönder
                $report = $messaging->sendMulticast($cmsg, $tokens);
                $success = $report->successes()->count();
                $fail = $report->failures()->count();

                \Log::info("[checkGiris12_20] => WM => user_id=$userId => success=$success, fail=$fail");

                // Sütunu 2 yap
                $logRow->checkGiris12_20 = 2;
                $logRow->save();
            }
        } else {
            \Log::info("[checkGiris12_20] => WM => checkGiris12_20=1 yok, devam ediyorum...");
        }

        // 1) Eski tatil/hafta sonu kontrolü
        if ($this->isHolidayOrWeekend()) {
            return;
        }

        \Log::info("[checkGiris12_20] tetiklendi (attendance logic)");

        // 2) $today = Carbon::today();
        //    Mevcut mantığın: "fcm_role='yes'", "whereDoesntHave('attendances')" vb.
        //    Aşağıdaki kodu **hiç bozmadan** koruyorum:
        $today = Carbon::today();
        $notChecked = User::where('fcm_role', 'yes')
            ->whereDoesntHave('attendances', function ($q) use ($today) {
                $q->whereDate('check_in_time', $today);
            })
            ->get();

        foreach ($notChecked as $u) {
            $tokens = UserFcmToken::where('user_id', $u->id)->pluck('fcm_token')->toArray();
            if (count($tokens) > 0) {
                // Zaten "FcmService::sendPush" veya Kreait ile "Giriş Yapmadınız" bildirimi
                // "Saat 12:20 => Artık çok geç, lütfen destek ekibiyle görüşün."
                // Bu senin mevcut (orijinal) kodunun bir parçası
                FcmService::sendPush($tokens, "Giriş Yapmadınız", "Saat 12:20 => Artık çok geç, lütfen destek ekibiyle görüşün.");
            }
        }
    }


    /**
     * 16:55 => kim sabah giriş yaptı ama çıkış yapmadı
     */
    protected function checkCikis1655()
    {
        \Log::info("[checkCikis1655] tetiklendi, now=" . now());

        // 0) ÖNCE workmanager_logs => checkCikis1655=1
        $todayDate = Carbon::today()->format('Y-m-d');

        $wmLogs = WorkmanagerLog::whereDate('date', $todayDate)
            ->where('checkCikis1655', 1)
            ->get();

        if (!$wmLogs->isEmpty()) {
            \Log::info("[checkCikis1655] => WM => " . count($wmLogs) . " adet => checkCikis1655=1 => FCM gonder");

            $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
            $messaging = $factory->createMessaging();

            foreach ($wmLogs as $logRow) {
                $userId = $logRow->user_id;

                // Kullanıcının tokenlarını al
                $tokens = UserFcmToken::where('user_id', $userId)->pluck('fcm_token')->toArray();
                if (empty($tokens)) {
                    \Log::info("[checkCikis1655] => WM => user_id=$userId => token yok => skip");
                    continue;
                }

                // Bildirim içeriği
                $title = "Çıkış Uyarısı (WorkManager)";
                $body = "16:55 => Konumdaysanız çıkış yapabilirsiniz! (workmanager_logs=1)";

                $message = CloudMessage::new()
                    ->withNotification([
                        'title' => $title,
                        'body' => $body,
                    ]);

                // Gönder
                $report = $messaging->sendMulticast($message, $tokens);
                $succ = $report->successes()->count();
                $fail = $report->failures()->count();

                \Log::info("[checkCikis1655] => WM => user_id=$userId => success=$succ, fail=$fail");

                // Sütun => 2
                $logRow->checkCikis1655 = 2;
                $logRow->save();
            }
        } else {
            \Log::info("[checkCikis1655] => WM => checkCikis1655=1 yok, devam ediyorum...");
        }


        // 1) Mevcut kod => Attendance tablosu
        //    "Giriş yapmış ama çıkış yapmamış" => check_out_time null
        $today = Carbon::today();
        $records = Attendance::whereDate('check_in_time', $today)
            ->whereNull('check_out_time')
            ->get();

        if ($records->isEmpty()) {
            \Log::info("[checkCikis1655] => hic user yok (cikis yapmamis) => iptal");
            return;
        }
        \Log::info("[checkCikis1655] => user sayisi=" . count($records) . " cikis yapmamış");

        // 3) Tüm tokenları tek arrayde toplayacağız
        $allTokens = [];
        foreach ($records as $att) {
            // $att->user_id => bu user sabah girmiş ama çıkış yapmamış
            $tokens = UserFcmToken::where('user_id', $att->user_id)->pluck('fcm_token')->toArray();
            if (!empty($tokens)) {
                $allTokens = array_merge($allTokens, $tokens);
            }
        }

        \Log::info("[checkCikis1655] => toplanan token sayisi=" . count($allTokens));

        if (empty($allTokens)) {
            \Log::info("[checkCikis1655] => hic token yok => push atilmiyor");
            return;
        }

        // 4) Kreait => sendMulticast
        $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
        $messaging = $factory->createMessaging();

        $message = CloudMessage::new()->withNotification([
            'title' => 'Çıkış Uyarısı',
            'body' => '16:55 => Konumdaysanız çıkış yapabilirsiniz',
        ]);

        $sendReport = $messaging->sendMulticast($message, $allTokens);

        // 5) Log sonuç
        $successCount = $sendReport->successes()->count();
        $failureCount = $sendReport->failures()->count();
        \Log::info("[checkCikis1655] => success=$successCount, failure=$failureCount");

        foreach ($sendReport->failures() as $fail) {
            $error = $fail->rawError();
            \Log::warning("[checkCikis1655] => hata=" . json_encode($error));
        }

        \Log::info("[checkCikis1655] => islem bitti.");
    }

    /**
     * 17:15 => son 5 dk
     */
    protected function checkCikis1715()
    {
        \Log::info("[checkCikis1715] tetiklendi, now=" . now());

        // 0) ÖNCE workmanager_logs => checkCikis1715=1
        $todayDate = Carbon::today()->format('Y-m-d');

        $wmLogs = WorkmanagerLog::whereDate('date', $todayDate)
            ->where('checkCikis1715', 1)
            ->get();

        if (!$wmLogs->isEmpty()) {
            \Log::info("[checkCikis1715] => WM => " . count($wmLogs) . " adet => checkCikis1715=1 => FCM gonderiyor.");

            $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
            $messaging = $factory->createMessaging();

            foreach ($wmLogs as $logRow) {
                $userId = $logRow->user_id;
                $tokens = UserFcmToken::where('user_id', $userId)->pluck('fcm_token')->toArray();

                if (empty($tokens)) {
                    \Log::info("[checkCikis1715] => WM => user_id=$userId => token yok => skip");
                    continue;
                }

                // Bildirim içeriği
                $title = "Çıkış Yaklaştı (WM)";
                $body = "17:15 => Son 5 dk içinde çıkış yapmalısınız (workmanager_logs=1)";

                $message = CloudMessage::new()
                    ->withNotification([
                        'title' => $title,
                        'body' => $body,
                    ]);

                $report = $messaging->sendMulticast($message, $tokens);
                $success = $report->successes()->count();
                $fail = $report->failures()->count();

                \Log::info("[checkCikis1715] => WM => user_id=$userId => success=$success, fail=$fail");

                // Sütun => 2
                $logRow->checkCikis1715 = 2;
                $logRow->save();
            }
        } else {
            \Log::info("[checkCikis1715] => WM => checkCikis1715=1 kayit yok, devam...");
        }

        // 1) Mevcut "attendance" kod => "Giriş yapmış ama çıkış yapmamış"
        $today = Carbon::today();

        $records = Attendance::whereDate('check_in_time', $today)
            ->whereNull('check_out_time')
            ->get();

        if ($records->isEmpty()) {
            \Log::info("[checkCikis1715] => hic user yok (cikis yapmamis) => iptal");
            return;
        }
        \Log::info("[checkCikis1715] => user sayisi=" . count($records) . " cikis yapmamış");

        // 3) Tüm tokenları tek arrayde toplayacağız
        $allTokens = [];
        foreach ($records as $att) {
            // $att->user_id => bu user sabah girmiş ama çıkış yapmamış
            $tokens = UserFcmToken::where('user_id', $att->user_id)->pluck('fcm_token')->toArray();
            if (!empty($tokens)) {
                $allTokens = array_merge($allTokens, $tokens);
            }
        }

        \Log::info("[checkCikis1715] => toplanan token sayisi=" . count($allTokens));

        if (empty($allTokens)) {
            \Log::info("[checkCikis1715] => hic token yok => push atilmiyor");
            return;
        }

        // 4) Kreait => sendMulticast
        $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
        $messaging = $factory->createMessaging();

        $message = CloudMessage::new()->withNotification([
            'title' => 'Çıkış Yaklaştı',
            'body' => '17:15 => Son 5 dk içinde çıkış yapmalısınız',
        ]);

        $sendReport = $messaging->sendMulticast($message, $allTokens);

        // 5) Log sonuç
        $successCount = $sendReport->successes()->count();
        $failureCount = $sendReport->failures()->count();
        \Log::info("[checkCikis1715] => success=$successCount, failure=$failureCount");

        foreach ($sendReport->failures() as $fail) {
            $error = $fail->rawError();
            \Log::warning("[checkCikis1715] => hata=" . json_encode($error));
        }

        \Log::info("[checkCikis1715] => islem bitti.");
    }


    /**
     * 17:20 ~ 17:30 => kim hâlâ çıkmadı
     */
    protected function checkCikisAfter1720()
    {
        \Log::info("[checkCikis1720] => tetiklendi, now=" . now());

        // 0) ÖNCE workmanager_logs => checkCikisAfter1740=1 (veya tablo sütun adın ne ise)
        $todayDate = Carbon::today()->format('Y-m-d');

        $wmLogs = WorkmanagerLog::whereDate('date', $todayDate)
            ->where('checkCikisAfter1740', 1)  // <-- tablo sütun ismini kontrol et
            ->get();

        if (!$wmLogs->isEmpty()) {
            \Log::info("[checkCikis1720] => WM => " . count($wmLogs) . " adet => checkCikisAfter1740=1 => FCM gonder");

            $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
            $messaging = $factory->createMessaging();

            foreach ($wmLogs as $logRow) {
                $userId = $logRow->user_id;
                $tokens = UserFcmToken::where('user_id', $userId)
                    ->pluck('fcm_token')
                    ->toArray();

                if (empty($tokens)) {
                    \Log::info("[checkCikis1720] => WM => user_id=$userId => token yok => skip");
                    continue;
                }

                // Bildirim içeriği
                $title = "Çıkış Yapmadınız (WorkManager)";
                $body = "Destek ekibiyle iletişime geçiniz. (workmanager_logs=1)";

                $message = CloudMessage::new()
                    ->withNotification([
                        'title' => $title,
                        'body' => $body,
                    ]);

                $report = $messaging->sendMulticast($message, $tokens);
                $succ = $report->successes()->count();
                $fail = $report->failures()->count();

                \Log::info("[checkCikis1720] => WM => user_id=$userId => success=$succ, fail=$fail");

                // Sütunu 2 yap => tekrar tetiklenmesin
                $logRow->checkCikisAfter1740 = 2;
                $logRow->save();
            }
        } else {
            \Log::info("[checkCikis1720] => WM => checkCikisAfter1740=1 yok, devam ediyorum...");
        }


        // 1) Mevcut "attendance" kodu => "Giriş yapmış ama çıkış yapmamış" => check_out_time null
        $today = Carbon::today();
        $records = Attendance::whereDate('check_in_time', $today)
            ->whereNull('check_out_time')
            ->get();

        if ($records->isEmpty()) {
            \Log::info("[checkCikis1720] => hic user yok (cikis yapmamis) => iptal");
            return;
        }
        \Log::info("[checkCikis1720] => user sayisi=" . count($records) . " cikis yapmamış");

        // 3) Tüm tokenları tek arrayde toplayacağız
        $allTokens = [];
        foreach ($records as $att) {
            // $att->user_id => bu user sabah girmiş ama çıkış yapmamış
            $tokens = UserFcmToken::where('user_id', $att->user_id)->pluck('fcm_token')->toArray();
            if (!empty($tokens)) {
                $allTokens = array_merge($allTokens, $tokens);
            }
        }

        \Log::info("[checkCikis1720] => toplanan token sayisi=" . count($allTokens));

        if (empty($allTokens)) {
            \Log::info("[checkCikis1720] => hic token yok => push atilmiyor");
            return;
        }

        // 4) Kreait => sendMulticast
        $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
        $messaging = $factory->createMessaging();

        $message = CloudMessage::new()->withNotification([
            'title' => 'Çıkış Yapmadınız',
            'body' => 'Destek ekibiyle iletişime geçiniz.',
        ]);

        $sendReport = $messaging->sendMulticast($message, $allTokens);

        // 5) Log sonuç
        $successCount = $sendReport->successes()->count();
        $failureCount = $sendReport->failures()->count();
        \Log::info("[checkCikis1720] => success=$successCount, failure=$failureCount");

        foreach ($sendReport->failures() as $fail) {
            $error = $fail->rawError();
            \Log::warning("[checkCikis1720] => hata=" . json_encode($error));
        }

        \Log::info("[checkCikis1720] => islem bitti.");
    }

    /**
     * 21:30 => hiç işlem yapmadı (gün sonunda)
     */
    protected function checkNoRecords2130()
    {
        // Log başlangıç
        \Log::info("[checkNoRecords2130] => tetiklendi, now=" . now());

        // 0) ÖNCE workmanager_logs => checkNoRecords2130=1
        $todayDate = Carbon::today()->format('Y-m-d');

        $wmLogs = WorkmanagerLog::whereDate('date', $todayDate)
            ->where('checkNoRecords2130', 1)
            ->get();

        if (!$wmLogs->isEmpty()) {
            \Log::info("[checkNoRecords2130] => WM => " . count($wmLogs) . " adet => checkNoRecords2130=1 => FCM gonderiyor.");

            $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
            $messaging = $factory->createMessaging();

            foreach ($wmLogs as $logRow) {
                $userId = $logRow->user_id;

                // Bu user'ın tokenlarını bul
                $tokens = UserFcmToken::where('user_id', $userId)
                    ->pluck('fcm_token')
                    ->toArray();

                if (empty($tokens)) {
                    \Log::info("[checkNoRecords2130] => WM => user_id=$userId => token yok => skip");
                    continue;
                }

                // Bildirim içeriği
                $title = "Hiç İşlem Yok (WorkManager)";
                $body = "Saat 21:30 => Bugün hiç giriş/çıkış işlemi yapmadınız! (workmanager_logs=1)";

                $message = CloudMessage::new()
                    ->withNotification([
                        'title' => $title,
                        'body' => $body,
                    ]);

                // Gönder
                $report = $messaging->sendMulticast($message, $tokens);
                $succ = $report->successes()->count();
                $fail = $report->failures()->count();

                \Log::info("[checkNoRecords2130] => WM => user_id=$userId => success=$succ, fail=$fail");

                // Sütunu 2 yap => tekrar tetiklenmesin
                $logRow->checkNoRecords2130 = 2;
                $logRow->save();
            }
        } else {
            \Log::info("[checkNoRecords2130] => WM => checkNoRecords2130=1 yok, devam ediyorum...");
        }

        // 1) Eski kod => "fcm_role='yes' user" + "hiç attendances kaydı yok" => toplu push
        $today = Carbon::today();

        $users = User::where('fcm_role', 'yes')->get();
        if ($users->isEmpty()) {
            \Log::info("[checkNoRecords2130] => hic user yok => iptal");
            return;
        }
        \Log::info("[checkNoRecords2130] => total user count=" . $users->count());

        // 2) Giriş yapmayan kullanıcıların tüm tokenlarını tek bir arrayde toplayacağız
        $allTokens = [];
        $noAttendanceCount = 0;
        $alreadyCheckInCount = 0;

        foreach ($users as $user) {
            // attendances tablosunda bugünün kaydı var mı?
            $count = Attendance::where('user_id', $user->id)
                ->whereDate('check_in_time', $today)
                ->count();

            if ($count == 0) {
                // => bu user hiç giriş yapmamış
                $noAttendanceCount++;

                // Bu user'ın tum token'larını bul
                $tokens = UserFcmToken::where('user_id', $user->id)
                    ->pluck('fcm_token')
                    ->toArray();

                // Tokenları $allTokens dizisine ekle
                if (!empty($tokens)) {
                    $allTokens = array_merge($allTokens, $tokens);
                }
            } else {
                // bu user zaten giriş yapmış
                $alreadyCheckInCount++;
            }
        }

        \Log::info("[checkNoRecords2130] => noAttendanceCount=$noAttendanceCount, alreadyCheckInCount=$alreadyCheckInCount");
        \Log::info("[checkNoRecords2130] => toplanan token sayisi=" . count($allTokens));

        // 3) Eger hic token yoksa => kimseye push atmayacağız
        if (empty($allTokens)) {
            \Log::info("[checkNoRecords2130] => hic token yok => push atilmiyor");
            return;
        }

        // 4) Kreait ile sendMulticast
        $factory2 = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
        $messaging2 = $factory2->createMessaging();

        // Bildirim içeriğini hazirla
        $message2 = CloudMessage::new()->withNotification([
            'title' => 'Giriş Hatırlatma',
            'body' => 'Saat 21:30 => Bugün hiç işlem yapmadınız!',
        ]);

        // Toplu gönderim
        $sendReport2 = $messaging2->sendMulticast($message2, $allTokens);

        $successCount = $sendReport2->successes()->count();
        $failureCount = $sendReport2->failures()->count();

        // Log
        \Log::info("[checkNoRecords2130] => success=$successCount, failure=$failureCount");

        // Hata detayları
        foreach ($sendReport2->failures() as $fail) {
            $error = $fail->rawError();
            \Log::warning("[checkNoRecords2130] => error=" . json_encode($error));
        }

        \Log::info("[checkNoRecords2130] => islem bitti.");
    }

    /**
     * Tatil / Hafta Sonu Kontrolü
     */
    protected function isHolidayOrWeekend()
    {
        $today = Carbon::today();

        // Hafta sonu
        if ($today->isWeekend()) {
            return true;
        }

        // Resmi tatil tablosu
        if (Holiday::whereDate('holidays', $today)->exists()) {
            return true;
        }

        return false;
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
