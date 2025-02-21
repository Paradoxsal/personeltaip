<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ManualNotification;
use App\Models\UserFcmToken;
use Kreait\Firebase\Factory as KreaitFactory;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Support\Facades\Log;

class CheckWorkManagerNotificationsCommand extends Command
{
    protected $signature = 'workmanager:check';
    protected $description = 'Checks the manual_notifications table for WorkManager commands (pause/resume) and sends them via FCM';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::info("[WorkManagerCheck] => Started at " . now());

        // Sadece 'pause' veya 'resume' action'ına sahip ve pending durumdaki kayıtları çekiyoruz.
        $pendingCommands = ManualNotification::where('status', 'pending')
            ->whereIn('action', ['pause', 'resume'])
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->get();

        if ($pendingCommands->isEmpty()) {
            Log::info("[WorkManagerCheck] => No pending WorkManager commands found.");
            return 0;
        }

        // Firebase Messaging yapılandırması
        $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
        $messaging = $factory->createMessaging();

        foreach ($pendingCommands as $command) {
            Log::info("[WorkManagerCheck] => Processing command #{$command->id} with action {$command->action}");

            // Hedef token’ları belirliyoruz.
            $allTokens = [];
            if ($command->target_type === 'all') {
                $allTokens = UserFcmToken::pluck('fcm_token')->toArray();
            } elseif ($command->target_type === 'user') {
                if ($command->user_id) {
                    $tokens = UserFcmToken::where('user_id', $command->user_id)
                        ->pluck('fcm_token')
                        ->toArray();
                    $allTokens = array_merge($allTokens, $tokens);
                }
            } elseif ($command->target_type === 'group') {
                if ($command->user_id) {
                    // Grup için user_id'ler virgülle saklanıyorsa:
                    $ids = explode(',', $command->user_id);
                    $ids = array_map('trim', $ids);
                    $tokens = UserFcmToken::whereIn('user_id', $ids)
                        ->pluck('fcm_token')
                        ->toArray();
                    $allTokens = array_merge($allTokens, $tokens);
                }
            }
            $allTokens = array_unique(array_filter($allTokens));
            if (empty($allTokens)) {
                Log::warning("[WorkManagerCheck] => Command #{$command->id} => No tokens found, marking as failed.");
                $command->status = 'failed';
                $command->save();
                continue;
            }

            // Push bildirimi için veri payload'ı ve notification içeriği hazırlıyoruz.
            if ($command->action === 'pause') {
                // Örneğin; body kısmında duration bilgisi de yer alabilir (burada örnek olarak '5' dakika)
                $data = [
                    'action'   => 'pause',
                    'duration' => '5',
                    'user_id'  => $command->user_id ?? '',
                ];
                $notificationTitle = 'WorkManager Komut';
                $notificationBody  = 'WorkManager durdurma komutu gönderildi.';
            } else { // resume
                $data = [
                    'action'  => 'resume',
                    'user_id' => $command->user_id ?? '',
                ];
                $notificationTitle = 'WorkManager Komut';
                $notificationBody  = 'WorkManager yeniden başlatma komutu gönderildi.';
            }

            $msg = CloudMessage::new()
                ->withNotification([
                    'title' => $notificationTitle,
                    'body'  => $notificationBody,
                ])
                ->withData($data);

            $sendReport = $messaging->sendMulticast($msg, $allTokens);
            $successCount = $sendReport->successes()->count();
            $failureCount = $sendReport->failures()->count();

            Log::info("[WorkManagerCheck] => Command #{$command->id} => Success: {$successCount}, Failure: {$failureCount}");

            if ($successCount > 0) {
                $command->status = 'sent';
                $command->sent_at = now();
            } else {
                $command->status = 'failed';
            }
            $command->save();

            // Hatalı token’ları loglama
            foreach ($sendReport->failures() as $failure) {
                $error = $failure->rawError();
                Log::warning("[WorkManagerCheck] => Command #{$command->id} Failure: " . json_encode($error));
            }
        }

        Log::info("[WorkManagerCheck] => Done.");
        return 0;
    }
}
