<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;          // user tablosu
use App\Models\UserHours;     // user_hours tablosu
use App\Models\SystemSettings;// system_settings tablosu
use Carbon\Carbon;

class HourController extends Controller
{
    /**
     * GET /entry-exit-hours
     * - Eğer user_hours tablosunda bu kullanıcı için start_time/end_time varsa onları döndür
     * - Yoksa system_settings (setting_type=entry_exit) tablosundan döndür
     */
    public function getEntryExitHours(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 401);
        }

        // 1) user_hours var mı?
        $userHour = \DB::table('user_hours')->where('user_id', $user->id)->first();

        if ($userHour) {
            // Kullanıcıya özel
            $morningStart = $userHour->morning_start_time;  // "07:00:00"
            $morningEnd = $userHour->morning_end_time;    // "12:00:00"
            $eveningStart = $userHour->evening_start_time;  // "13:00:00"
            $eveningEnd = $userHour->evening_end_time;    // "17:30:00"
            $hasCustom = true;
        } else {
            // 2) system_settings (setting_type=entry_exit)
            $system = \DB::table('system_settings')
                ->where('setting_type', 'entry_exit')
                ->first();
            // Varsayılan
            $morningStart = $system ? $system->morning_start_time : '08:00:00';
            $morningEnd = $system ? $system->morning_end_time : '12:00:00';
            $eveningStart = $system ? $system->evening_start_time : '13:00:00';
            $eveningEnd = $system ? $system->evening_end_time : '17:00:00';
            $hasCustom = false;
        }

        return response()->json([
            'success' => true,
            'morning_start_time' => $morningStart,
            'morning_end_time' => $morningEnd,
            'evening_start_time' => $eveningStart,
            'evening_end_time' => $eveningEnd,
            'has_custom' => $hasCustom,
        ], 200);
    }

}
