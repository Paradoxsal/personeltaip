<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Models\Holiday;
use App\Models\HalfdayRequest;
use Carbon\Carbon;
use DB; // <-- system_settings için

class CheckAllController extends Controller
{
    public function checkAll(Request $request)
    {
        // 1) Kullanıcı
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'login_needed',
                'reason' => 'no_token'
            ], 200);
        }

        // 2) FCM
        $hasFcm = UserFcmToken::where('user_id', $user->id)->exists();
        if (!$hasFcm) {
            return response()->json([
                'status' => 'login_needed',
                'reason' => 'no_fcm'
            ], 200);
        }

        // 3) Ban
        if ($user->banned == 1) {
            return response()->json([
                'status' => 'blocked',
                'reason' => 'banned'
            ], 200);
        }

        // 4) Cihaz
        $reqDevice = $request->input('device_info');
        if ($reqDevice && $user->device_info) {
            if ($reqDevice !== $user->device_info) {
                return response()->json([
                    'status' => 'blocked',
                    'reason' => 'device_not_matched'
                ], 200);
            }
        }
        if ($user->cihaz_yetki != 1) {
            return response()->json([
                'status' => 'blocked',
                'reason' => 'device_not_allowed'
            ], 200);
        }

        // Hafta sonu / Tatil / İzin
        $today = Carbon::today();

        //  Hafta sonu (istersen devre dışı)
        if ($today->isWeekend()) {
            return response()->json([
                'status' => 'blocked',
                'reason' => 'weekend'
            ], 200);
        }

        // 5b) Tatil
        $holiday = Holiday::where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();
        if ($holiday) {
            return response()->json([
                'status' => 'blocked',
                'reason' => 'holiday'
            ], 200);
        }

        // 5c) İzin / rapor
        $currentHour = Carbon::now()->hour;
        $izin = HalfdayRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('id', 'desc')
            ->first();

        // 5c) İzin / rapor
        $izin = HalfdayRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('id', 'desc')
            ->first();

        if ($izin) {
            $type = $izin->type; // morning, afternoon, full_day, rapor
            $currentHour = Carbon::now()->hour;

            switch ($type) {
                case 'morning':
                    // Eğer saat henüz 12:00 olmamışsa => blocked
                    if ($currentHour < 12) {
                        return response()->json([
                            'status' => 'blocked',
                            'reason' => 'izin_or_rapor',
                            'izin_detail' => 'morning', // ister gönder, ister gönderme
                        ], 200);
                    }
                    // 12:00 sonrası => engel yok => devam => break
                    break;

                case 'afternoon':
                    // Eğer saat 12:00 veya geçtiyse => blocked
                    if ($currentHour >= 12) {
                        return response()->json([
                            'status' => 'blocked',
                            'reason' => 'izin_or_rapor',
                            'izin_detail' => 'afternoon',
                        ], 200);
                    }
                    // 12:00'dan önce => engel yok => break
                    break;

                case 'full_day':
                case 'rapor':
                    // Tüm gün engelli
                    return response()->json([
                        'status' => 'blocked',
                        'reason' => 'izin_or_rapor',
                        'izin_detail' => $type,  // 'full_day' veya 'rapor'
                    ], 200);

                default:
                    // normal -> break
                    break;
            }
        }




        // 6) Versiyon kontrol => system_settings tablosu
        $versionRow = DB::table('system_settings')
            ->where('setting_type', 'new_version')
            ->first();

        if ($versionRow) {
            // version_status: 'send' => yeni versiyon var
            if ($versionRow->version_status === 'send') {
                return response()->json([
                    'status' => 'version_update',
                    'version_link' => $versionRow->version_link ?? '',
                    'version_desc' => $versionRow->version_desc ?? '',
                ], 200);
            }
        }

        // 7) Her şey OK => normal
        return response()->json([
            'status' => 'ok',
            'reason' => null
        ], 200);
    }
}
