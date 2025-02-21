<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserFcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // E-posta veya telefon numarası kabul edilecek şekilde doğrulama
        $validator = Validator::make($request->all(), [
            'email' => 'required|string', // Burada sadece string kontrol ediyoruz, email doğrulaması kaldırıldı
            'password' => 'required|string',
            'device_info' => 'required|string',
            'fcm_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        // 1) Kullanıcı bul (e-posta veya telefon numarası ile)
        $user = User::where('email', $request->email)
            ->orWhere('phone', $request->email) // Telefon numarasını da kontrol et
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Kullanıcı bulunamadı'], 404);
        }


        // 2) Ban kontrol
        if ($user->banned == 1) {
            return response()->json([
                'message' => 'Hesabınız banlı.',
                'banned' => true,
                'ban_reason' => $user->banned_log,
            ], 403);
        }

        // 3) Şifre
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Hatalı şifre'], 401);
        }

        // 4) "tek cihaz" kuralı
        //    4.a) Mevcut user_device kaydı var mı?
        $existingDevice = UserDevice::where('user_id', $user->id)->first();

        if (!$existingDevice) {
            // =============== HENÜZ CİHAZ YOK => YENİ CİHAZ ===============
            // random ek
            $originalInfo = $request->input('device_info');
            $randomSuffix = Str::random(6);
            $finalInfo = $originalInfo . '_' . $randomSuffix;

            // Kaydet
            UserDevice::create([
                'user_id' => $user->id,
                'device_info' => $finalInfo,
                'cihaz_yetki' => 1,
            ]);
            $user->device_info = $finalInfo;
            $user->cihaz_yetki = 1;
            $user->save();

        } else {
            // =============== ZATEN BİR CİHAZ VAR ===============
            // Bu cihaz user_device tablosundaki "device_info" ile mi eşleşiyor?
            $existingInfo = $existingDevice->device_info;
            $incomingInfo = $request->input('device_info');

            // 4.b) AYNI CIHAZ MI?
            if ($incomingInfo == $existingInfo) {
                // Evet => Aynı cihaz => normal login, random ek YOK
                // => user->device_info aynı kalır
                // => userDevice->device_info değişmez
            } else {
                // FARKLI CİHAZ => tek cihaz kuralı => hata
                return response()->json([
                    'message' => 'Bu hesaba farklı bir cihaz zaten tanımlanmış. Tek cihaz hakkınız var.',
                    'success' => false
                ], 403);
            }
        }

        // 5) FCM
        if ($request->filled('fcm_token')) {
            $fcmToken = $request->input('fcm_token');
            if (!empty($fcmToken)) {
                UserFcmToken::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'fcm_token' => $fcmToken,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $user->update(['fcm_role' => 'yes']);
            }
        }

        // 6) Eski tokenları sil
        $user->tokens()->delete();

        // 7) Yeni Token => 15 gün
        $tokenResult = $user->createToken('auth_token');
        $token = $tokenResult->plainTextToken;
        $expiresAt = now()->addDays(15);

        $tokenId = $user->tokens()->latest('id')->first()->id;
        \DB::table('personal_access_tokens')
            ->where('id', $tokenId)
            ->update(['expires_at' => $expiresAt]);

        // Cihaz bilgisi => user->device_info
        // (Eğer "yeni cihaz" ise random ekli, eğer "aynı cihaz" ise eskisi)
        $registeredDeviceInfo = $user->device_info;

        return response()->json([
            'message' => 'Giriş başarılı',
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toDateTimeString(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'fcm_role' => $user->fcm_role,
                'device_info' => $registeredDeviceInfo
            ]
        ], 200);
    }
}
