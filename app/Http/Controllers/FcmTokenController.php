<?php

// app/Http/Controllers/FcmTokenController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserFcmToken;

class FcmTokenController extends Controller
{
    public function saveToken(Request $request)
    {
        $request->validate([
            'fcm_token'  => 'required|string',
            'device_info'=> 'nullable|string',
        ]);

        // sanctum -> $request->user() ya da Auth::user()
        $user = $request->user();
        if (!$user) {
            return response()->json(['message'=>'User not authenticated'],401);
        }

        // Aynı token varsa güncelle (opsiyonel)
        $existing = UserFcmToken::where('fcm_token',$request->fcm_token)->first();
        if ($existing) {
            // Token zaten var => sadece updated_at güncelle
            $existing->touch(); // $existing->updated_at = now(); $existing->save();
        } else {
            // Yeni kayıt
            UserFcmToken::create([
                'user_id'    => $user->id,
                'fcm_token'  => $request->fcm_token,
                'device_info'=> $request->device_info
            ]);
        }

        // users tablosunda fcm_role='yes'
        $user->update(['fcm_role'=>'yes']);

        return response()->json([
            'message'=>'FCM token kaydedildi',
            'status'=>true
        ],200);
    }
}
