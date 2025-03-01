<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Models\Holiday;
use App\Models\HalfdayRequest;
use Carbon\Carbon;
use DB;
use App\Http\Controllers\WeekendControlController;

class CheckAllController extends Controller
{
    public function checkAll(Request $request)
    {
        // 1) Kullanıcı kontrolü
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'login_needed',
                'reason' => 'no_token'
            ], 200);
        }

        // 2) FCM kontrolü
        $hasFcm = UserFcmToken::where('user_id', $user->id)->exists();
        if (!$hasFcm) {
            return response()->json([
                'status' => 'login_needed',
                'reason' => 'no_fcm'
            ], 200);
        }

        // 3) Ban kontrolü
        if ($user->banned == 1) {
            return response()->json([
                'status' => 'blocked',
                'reason' => 'banned'
            ], 200);
        }

        // 4) Cihaz kontrolü
        $reqDevice = $request->input('device_info');
        if ($reqDevice && $user->device_info && $reqDevice !== $user->device_info) {
            return response()->json([
                'status' => 'blocked',
                'reason' => 'device_not_matched'
            ], 200);
        }
        if ($user->cihaz_yetki != 1) {
            return response()->json([
                'status' => 'blocked',
                'reason' => 'device_not_allowed'
            ], 200);
        }

        // --- Hafta sonu, tatil ve izin kontrolleri ---
        $today = Carbon::today();

        // Hafta sonu kontrolü: Bugün hafta sonu ise, WeekendControlController fonksiyonunu kullan.
        if ($today->isWeekend() && !WeekendControlController::isWeekendActiveForUser($user)) {
            return response()->json([
                'status' => 'blocked',
                'reason' => 'weekend'
            ], 200);
        }

        // 5) Tatil kontrolü
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

        // 6) İzin / Rapor kontrolü
        $currentHour = Carbon::now()->hour;
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
                    if ($currentHour < 12) {
                        return response()->json([
                            'status' => 'blocked',
                            'reason' => 'izin_or_rapor',
                            'izin_detail' => 'morning'
                        ], 200);
                    }
                    break;
                case 'afternoon':
                    if ($currentHour >= 12) {
                        return response()->json([
                            'status' => 'blocked',
                            'reason' => 'izin_or_rapor',
                            'izin_detail' => 'afternoon'
                        ], 200);
                    }
                    break;
                case 'full_day':
                case 'rapor':
                    return response()->json([
                        'status' => 'blocked',
                        'reason' => 'izin_or_rapor',
                        'izin_detail' => $type
                    ], 200);
                default:
                    break;
            }
        }

        // 7) Versiyon kontrolü (system_settings)
        $versionRow = DB::table('system_settings')
            ->where('setting_type', 'new_version')
            ->first();

        if ($versionRow && $versionRow->version_status === 'send') {
            return response()->json([
                'status' => 'version_update',
                'version_link' => $versionRow->version_link ?? '',
                'version_desc' => $versionRow->version_desc ?? '',
            ], 200);
        }

        // 8) Tüm kontroller başarılı ise
        return response()->json([
            'status' => 'ok',
            'reason' => null
        ], 200);
    }
}
