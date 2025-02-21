<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDevice;          // Tablo: user_devices
use App\Models\PersonalAccessToken; // Tablo: personal_access_tokens
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PhoneSettingsController extends Controller
{
    /**
     * index: Tüm kullanıcıları listeler
     */
    public function index()
    {
        // Tüm kullanıcıları al
        $users = User::all();

        // Her kullanıcıya personal_access_tokens tablosundan token_string bul
        // personal_access_tokens tablosunda user_id yok, "tokenable_id" var.
        foreach ($users as $u) {
            // tokenable_id = $u->id kaydını bul
            $tokenRecord = PersonalAccessToken::where('tokenable_id', $u->id)->first();
            $u->token_string = $tokenRecord ? $tokenRecord->token : null;
        }

        // phone-settings.blade'e gönder
        return view('laravel-examples.phone-settings', compact('users'));
    }

    /**
     * update: Modal'dan gelen formu işler (Route: phoneSettings.update)
     */
    public function update(Request $request, $userId)
    {
        // 1) User tablosunda device_info, device_yetki güncelle
        $user = User::findOrFail($userId);

        // A) device_info
        $deviceInfoOption = $request->input('device_info_option'); // 1,2,3
        $newDeviceInfo    = $request->input('new_device_info');    // text

        if ($deviceInfoOption == '2') {
            // cihaz bilgisi kaldır
            $user->device_info = null;
        } elseif ($deviceInfoOption == '3') {
            // yeni cihaz ekle
            $user->device_info = $newDeviceInfo;
        }
        // '1' => "mevcut cihaz kalsın", hiç dokunma

        // B) device_yetki
        $deviceYetkiOption = $request->input('device_yetki_option'); // "ver" => 1, "kaldir" => 0
        if ($deviceYetkiOption == 'ver') {
            $user->device_yetki = 1;
        } else {
            $user->device_yetki = 0;
        }

        $user->save();

        // 2) user_devices tablosunda da aynı şekilde update
        $userDevice = UserDevice::where('user_id', $userId)->first();
        if ($userDevice) {
            // device_info
            if ($deviceInfoOption == '2') {
                $userDevice->device_info = null;
            } elseif ($deviceInfoOption == '3') {
                $userDevice->device_info = $newDeviceInfo;
            }

            // device_yetki
            if ($deviceYetkiOption == 'ver') {
                $userDevice->device_yetki = 1;
            } else {
                $userDevice->device_yetki = 0;
            }

            $userDevice->save();
        }

        // 3) personal_access_tokens => token_option
        // - sabit => dokunma
        // - sifirla => random 10 haneli token
        $tokenOption = $request->input('token_option');
        if ($tokenOption == 'sifirla') {
            // tokenable_id = $userId kaydı bul
            $tokenRecord = PersonalAccessToken::where('tokenable_id', $userId)->first();
            if ($tokenRecord) {
                $randomToken = Str::random(10); // 10 karakter
                $tokenRecord->token = $randomToken;
                $tokenRecord->save();
            }
        }
        // sabit => hiç işlem yok

        return redirect()->back()->with('success', 'Telefon ayarları güncellendi.');
    }
}
