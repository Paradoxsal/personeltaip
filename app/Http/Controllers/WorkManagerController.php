<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\WorkmanagerLog;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\HalfdayRequest;
use App\Models\Holidays;
use App\Models\WorkmanagerLogs;
use App\Models\WorkmanagerSituation;
use Carbon\Carbon;
use DB;

class WorkManagerController extends Controller
{
    // 1) daily-check
    public function dailyCheck(Request $request)
    {
        $userId = $request->user_id;
        $today = Carbon::now()->format('Y-m-d');

        // Hafta sonu mu?
        $dayOfWeek = Carbon::now()->dayOfWeek; // 0=Pazar, 6=Cumartesi
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            return response()->json([
                'start' => 0,
                'message' => 'Bugün hafta sonu'
            ], 200);
        }

        // Bayram/tatil mi?
        $holidays = Holiday::where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();
        if ($holidays) {
            return response()->json([
                'start' => 0,
                'message' => 'Bugün resmi tatil / bayram: ' . $holidays->holiday_name
            ], 200);
        }

        // İzin/rapor?
        $izin = HalfdayRequest::where('user_id', $userId)
            ->where('date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->where('end_date', '>=', $today)->orWhereNull('end_date');
            })
            ->where('status', 'approved')
            ->first();

        if ($izin) {
            if ($izin->type == 'full_day' || $izin->type == 'rapor') {
                return response()->json([
                    'start' => 0,
                    'message' => 'Bugün izinlisiniz / raporlusunuz'
                ], 200);
            }
            // Yarım gün sabah/öğleden sonra vs...
        }

        // normal gün => start=1
        return response()->json([
            'start' => 1,
            'message' => 'Bugün normal mesai'
        ], 200);
    }

    // 2) create-daily-records
    public function createDailyRecords(Request $request)
    {
        $userId = $request->user_id;
        $today = Carbon::now()->format('Y-m-d');
    
        // 1) workmanager_logs tablosunda “bugün” için kayıt var mı?
        $exists = WorkmanagerLog::where('user_id', $userId)
            ->where('date', $today)
            ->first();
    
        // Yoksa yeni kayıt oluştur
        if (!$exists) {
            $log = new WorkmanagerLog();
            $log->user_id = $userId;
            $log->date = $today;
            $log->sendMorningGunaydin = 0;
            // ... diğer sütunları varsayılan değerlerle bırakabilirsin
            $log->save();
        }
    
        // 2) workmanager_situation tablosunda da bugüne ait kayıt var mı?
        $sitExists = WorkmanagerSituation::where('user_id', $userId)
            ->whereDate('created_at', $today)
            ->first();
    
        // Yoksa yeni kayıt oluştur
        if (!$sitExists) {
            $situation = new WorkmanagerSituation();
            $situation->user_id = $userId;
            $situation->active_hours = null;
            $situation->is_active = 1; // sabah genelde aktif
            $situation->location_info = null;
            // ... diğer sütunlar varsayılan
            $situation->save();
        }
    
        return response()->json(['status' => 'ok'], 200);
    }
    

    // 3) update-logs => flag
    public function updateLogs(Request $request)
    {
        $flag = $request->flag;
        $userId = $request->user_id;
        $today = Carbon::now()->format('Y-m-d');

        $log = WorkmanagerLog::where('user_id', $userId)->where('date', $today)->first();
        if (!$log) {
            return response()->json(['status' => 'error', 'msg' => 'Log not found'], 404);
        }

        if ($flag == 'sendMorningGunaydin') {
            $log->sendMorningGunaydin = 1;
        } else if ($flag == 'checkGiris09') {
            $log->checkGiris09 = 1;
        } else if ($flag == 'checkGiris11') {
            $log->checkGiris11 = 1;
        } else if ($flag == 'checkGiris12_20') {
            $log->checkGiris12_20 = 1;
        } else if ($flag == 'checkCikis1655') {
            $log->checkCikis1655 = 1;
        } else if ($flag == 'checkCikis1715') {
            $log->checkCikis1715 = 1;
        } else if ($flag == 'checkCikisAfter1740') {
            $log->checkCikisAfter1740 = 1;
        } else if ($flag == 'checkNoRecords2130') {
            $log->checkNoRecords2130 = 1;
        }

        $log->save();
        return response()->json(['status' => 'ok'], 200);
    }

    // 4) store-geolocation
    public function storeGeolocation(Request $request)
    {
        $userId = $request->user_id;
        $lat = $request->latitude;
        $lng = $request->longitude;
        // kaydetmek istersen...
        return response()->json(['status' => 'ok'], 200);
    }

    // store-situation-data => hourly update
    public function storeSituationData(Request $request)
    {
        $userId = $request->input('user_id');
        $activeHours = $request->input('active_hours');
        $isActive = $request->input('is_active');
        $locationInfo = $request->input('location_info');

        $today = Carbon::now()->format('Y-m-d');
        $sit = WorkmanagerSituation::where('user_id', $userId)
            ->whereDate('created_at', $today)
            ->first();

        if (!$sit) {
            $sit = new WorkmanagerSituation();
            $sit->user_id = $userId;
        }
        $sit->is_active = $isActive;
        $sit->active_hours = $activeHours;
        $sit->location_info = $locationInfo;
        $sit->save();

        return response()->json(['status' => 'ok'], 200);
    }

    // checkIn location
    public function getCheckInLocation(Request $request)
    {
        $userId = $request->input('user_id');
        if (!$userId) {
            return response()->json(['message' => 'user_id parametresi gerekli'], 422);
        }
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'check_in_location' => $user->check_in_location,
        ]);
    }

    // isCheckedIn
    public function isCheckedIn(Request $request)
    {
        $userId = $request->input('user_id');
        $today = date('Y-m-d');

        $exists = DB::table('attendance')
            ->where('user_id', $userId)
            ->whereDate('check_in_time', $today)
            ->exists();

        return response()->json(['checkedIn' => $exists], 200);
    }

    // checkOut location
    public function getCheckOutLocation(Request $request)
    {
        $userId = $request->input('user_id');
        if (!$userId) {
            return response()->json(['message' => 'user_id parametresi gerekli'], 422);
        }
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json([
            'check_out_location' => $user->check_out_location,
        ]);
    }

    // GET => attendance/check-in?user_id=..
    public function statusCheckIn(Request $request)
    {
        $userId = $request->query('user_id');
        $today = date('Y-m-d');

        $exists = DB::table('attendance')
            ->where('user_id', $userId)
            ->whereDate('check_in_time', $today)
            ->exists();

        return response()->json(['checked_in_today' => $exists], 200);
    }

    // GET => attendance/check-out?user_id=..
    public function statusCheckOut(Request $request)
    {
        $userId = $request->query('user_id');
        $today = date('Y-m-d');

        $exists = DB::table('attendance')
            ->where('user_id', $userId)
            ->whereDate('check_out_time', $today)
            ->exists();

        return response()->json(['checked_out_today' => $exists], 200);
    }

    public function statusOvertime(Request $request)
    {
        $userId = $request->query('user_id');

        // shift_logs tablosunda user_id => eğer bugüne ait mesai kaydı var mı?
        // Örnek:
        $today = date('Y-m-d');

        // Diyelim shift_logs tablosunda (user_id, date, is_overtime=1) gibi
        $exists = DB::table('shift_logs')
            ->where('user_id', $userId)
            ->whereDate('date', $today)
            ->exists();

        return response()->json([
            'has_overtime_today' => $exists
        ], 200);
    }

    public function hasUserHours(Request $request)
    {
        $userId = $request->query('user_id');
        $exists = DB::table('user_hours')
            ->where('user_id', $userId)
            ->exists();

        return response()->json([
            'hasUserHours' => $exists
        ], 200);
    }

    public function isTodayOff(Request $request)
    {
        $userId = $request->query('user_id');
        $today = date('Y-m-d');

        // Tam gün izinli mi?
        // Örneğin: HalfdayRequest ya da full_day/rapor/tatil gibi logic
        // veya dailyCheck => start=0 ??? 
        // Aşağıda basit bir örnek:

        // 1) Hafta sonu mu?
       /* $dayOfWeek = date('w'); // 0=Pazar, 6=Cumartesi
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            return response()->json(['isOff' => true], 200);
        }*/

        // 2) Tatil/izin kontrol (örnek dailyCheck logic):
        // Tam gün izinse => isOff=true
        $izin = DB::table('halfday_requests')
            ->where('user_id', $userId)
            ->where('date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->where('end_date', '>=', $today)->orWhereNull('end_date');
            })
            ->where('type', 'full_day') // tam gun
            ->where('status', 'approved')
            ->first();

        if ($izin) {
            return response()->json(['isOff' => true], 200);
        }

        // 3) Bayram/tatil => holiday tablosu
        $holiday = DB::table('holidays')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();
        if ($holiday) {
            return response()->json(['isOff' => true], 200);
        }

        // Değilse => false
        return response()->json(['isOff' => false], 200);
    }
}
