<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;  // Rastgele ek için

class DeviceController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'device_info' => 'required|string',
        ]);

        $user = $request->user();

        // Ban kontrolü
        if ($user->banned == 1) {
            return response()->json([
                'message'    => 'Hesabınız banlı.',
                'banned'     => true,
                'ban_reason' => $user->banned_log
            ], 403);
        }

        // Her durumda, gelen device_info + random suffix
        $originalInfo = $request->input('device_info');
        $randomSuffix = Str::random(6);  // 6 karakter: harf/rakam karışık
        $finalInfo    = $originalInfo . '_' . $randomSuffix;

        // Kaydet
        $user->device_info = $finalInfo;
        $user->cihaz_yetki = 1;
        $user->save();

        // Cevap
        return response()->json([
            'message'     => 'Device registered with random suffix',
            'device_info' => $finalInfo,
        ], 201);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'device_info' => 'required|string',
        ]);

        $user = $request->user();
        if ($user->banned == 1) {
            return response()->json([
                'message'    => 'Hesabınız banlı.',
                'banned'     => true,
                'ban_reason' => $user->banned_log
            ], 403);
        }

        // device_info eşleşmesi ve yetki kontrolü
        if ($user->device_info !== $request->device_info || $user->cihaz_yetki !== 1) {
            return response()->json(['message' => 'Device verification failed'], 403);
        }

        return response()->json(['message' => 'Device verified'], 200);
    }
}
