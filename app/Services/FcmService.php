<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FcmService
{
    /**
     * Tek token veya çoklu tokena push göndermek için.
     *
     * @param array|string $tokens  Tek string token veya dizi
     * @param string $title
     * @param string $body
     * @return mixed
     */
    public static function sendPush($tokens, $title, $body)
    {
        // eğer $tokens tek bir string ise, registration_ids yerine to kullanabilirsin
        // ama genelde diziye çevirip çoklu gönderim yaparız
        $tokenArray = is_array($tokens) ? $tokens : [$tokens];

        $url = 'https://fcm.googleapis.com/fcm/send';

        // .env içine FCM_SERVER_KEY=AAAAxxxxxx şeklinde koyacaksın
        $serverKey = env('FCM_SERVER_KEY');

        // Notification verisi
        $data = [
            "registration_ids" => $tokenArray,
            "notification" => [
                "title" => $title,
                "body"  => $body,
                "sound" => "default"
            ],
            // data payload ekleyebilirsin
            "data" => [
                "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                // ek veriler...
            ]
        ];

        // HTTP ile gönder
        $response = Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type'  => 'application/json',
        ])->post($url, $data);

        return $response->json();
    }
}
