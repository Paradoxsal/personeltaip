<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ManualNotification;
use App\Models\UserFcmToken;
use Kreait\Firebase\Factory as KreaitFactory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckManualNotificationsCommand extends Command implements ShouldQueue
{
    protected $signature = 'notifications:check';
    protected $description = 'Checks manual_notifications table for data-only notifications (action=data) and sends them.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::info("[CheckManualNotificationsCommand] => started at " . now());

        // 1) PENDING kayıtları bul
        $pendingNotifs = ManualNotification::where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->get();

        if ($pendingNotifs->isEmpty()) {
            Log::info("[CheckManualNotificationsCommand] => no pending notifications => done");
            return 0;
        }

        // 2) Firebase Messaging
        $factory = (new KreaitFactory)->withServiceAccount(config('services.firebase.credentials_file'));
        $messaging = $factory->createMessaging();

        foreach ($pendingNotifs as $notif) {
            Log::info("[CheckManualNotificationsCommand] => processing notif#{$notif->id}, title={$notif->title}");

            // **IF** => action == 'data' => data-only push
            if ($notif->action !== 'data') {
                // Bu komut sadece data-only bildirimleri göndermek için
                Log::info("[CheckManualNotificationsCommand] => notif#{$notif->id} => action != data => skip");
                continue;
            }

            // 2A) Tokenları toplayacağız
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
                    $ids = explode(',', $notif->user_id); // "1,2,3"
                    $ids = array_map('trim', $ids);
                    $tokens = UserFcmToken::whereIn('user_id', $ids)
                        ->pluck('fcm_token')
                        ->toArray();
                    $allTokens = array_merge($allTokens, $tokens);
                }
            }

            $allTokens = array_unique(array_filter($allTokens));
            if (empty($allTokens)) {
                Log::warning("[CheckManualNotificationsCommand] => notif#{$notif->id} => no tokens => mark failed");
                $notif->status = 'failed';
                $notif->save();
                continue;
            }

            Log::info("[CheckManualNotificationsCommand] => notif#{$notif->id}, tokenCount=" . count($allTokens));

            // 3) Data-Only CloudMessage
            // (title/body eklemesek de olur, ama istersen veriyi saklayabilirsin.)
            $dataPayload = [
                'action'  => 'getLocation',
                'title'   => $notif->title ?? '',
                'body'    => $notif->body  ?? '',
            ];

            $message = CloudMessage::new()
                ->withData($dataPayload)
                ->withAndroidConfig(AndroidConfig::fromArray([
                    'priority' => 'high',
                ]))
                ->withApnsConfig(ApnsConfig::fromArray([
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'content-available' => 1,
                        ],
                    ],
                ]));

            // 4) sendMulticast => Data-only push => kullanıcıya görünmez
            $report = $messaging->sendMulticast($message, $allTokens);
            $successCount = $report->successes()->count();
            $failureCount = $report->failures()->count();

            Log::info("[CheckManualNotificationsCommand] => notif#{$notif->id} => success=$successCount, failure=$failureCount");

            if ($successCount > 0) {
                $notif->status  = 'sent';
                $notif->sent_at = now();
            } else {
                $notif->status = 'failed';
            }
            $notif->save();

            // Hata detayları log
            foreach ($report->failures() as $fail) {
                $err = $fail->rawError();
                Log::warning("[CheckManualNotificationsCommand] => notif#{$notif->id} => error=" . json_encode($err));
            }
        }

        Log::info("[CheckManualNotificationsCommand] => done");

        return 0; // success
    }
}
