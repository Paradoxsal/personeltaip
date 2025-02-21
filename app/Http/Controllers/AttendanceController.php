<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ShiftLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function checkIn(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = Auth::user();

        // 1) Giriş konum parse
        $dbCoords = explode(',', $user->check_in_location);
        if (count($dbCoords) != 2) {
            return response()->json(['message' => 'Sistem lokasyon verisini çözümleyemedi.'], 400);
        }
        $dbLat = (float) trim($dbCoords[0]);
        $dbLng = (float) trim($dbCoords[1]);

        $reqLat = (float) $request->latitude;
        $reqLng = (float) $request->longitude;

        // 2) Mesafe hesapla (50 metre sınır)
        $distance = $this->calculateDistance($dbLat, $dbLng, $reqLat, $reqLng);
        if ($distance > 50) {
            return response()->json(['message' => 'Giriş için uygun lokasyonda değilsiniz.'], 400);
        }

        // 3) Bugün zaten giriş yapılmış mı
        $today = Carbon::today();
        $alreadyCheckedInToday = Attendance::where('user_id', $user->id)
            ->whereDate('check_in_time', $today)
            ->exists();
        if ($alreadyCheckedInToday) {
            return response()->json(['message' => 'Bugün zaten giriş yaptınız.'], 400);
        }

        // 4) *** Saat kontrolü KALDIRILDI *** 
        //    ÖNCEKİDE: 07:00–12:00 aralığını denetlerdi.
        //    Artık bu kontrol Flutter tarafında yapılacak.

        // 5) Kayıt oluştur
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'check_in_time' => now(),
            'check_in_location' => "$reqLat,$reqLng",
        ]);

        return response()->json([
            'message' => 'Giriş işlemi başarılı',
            'attendance' => $attendance
        ], 200);
    }

    public function checkOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = Auth::user();

        // 1) *** Saat kontrolü KALDIRILDI ***
        //    ÖNCEKİDE: 12:00–17:20 aralığını denetlerdi.
        //    Artık Flutter tarafında kontrol edilecek.

        // 2) O günün aktif giriş kaydı var mı
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('check_in_time', now()->toDateString())
            ->whereNull('check_out_time')
            ->first();
        if (!$attendance) {
            return response()->json(['message' => 'Bugün giriş yapmadınız, çıkış yapamazsınız.'], 400);
        }

        // 3) Çıkış konum parse
        $dbCoords = explode(',', $user->check_out_location);
        if (count($dbCoords) != 2) {
            return response()->json(['message' => 'Sistem lokasyon verisini çözümleyemedi.'], 400);
        }
        $dbLat = (float) trim($dbCoords[0]);
        $dbLng = (float) trim($dbCoords[1]);

        $reqLat = (float) $request->latitude;
        $reqLng = (float) $request->longitude;

        // 4) Mesafe hesabı (50 metre sınır)
        $distance = $this->calculateDistance($dbLat, $dbLng, $reqLat, $reqLng);
        if ($distance > 50) {
            return response()->json(['message' => 'Çıkış için uygun lokasyonda değilsiniz.'], 400);
        }

        // 5) Güncelle
        $attendance->update([
            'check_out_time' => now(),
            'check_out_location' => "$reqLat,$reqLng",
        ]);

        return response()->json([
            'message' => 'Çıkış işlemi başarılı',
            'attendance' => $attendance
        ], 200);
    }

    // Bugün giriş yapmış mı
    public function statusCheckIn(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        $checkedIn = Attendance::where('user_id', $user->id)
            ->whereDate('check_in_time', $today)
            ->exists();
        return response()->json(['checked_in_today' => $checkedIn], 200);
    }

    // Bugün çıkış yapmış mı
    public function statusCheckOut(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        $checkedOut = Attendance::where('user_id', $user->id)
            ->whereDate('check_out_time', $today)
            ->exists();
        return response()->json(['checked_out_today' => $checkedOut], 200);
    }

    // Bugünkü attendance kayıtlarını döndürür
    public function today(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('check_in_time', $today)
            ->first();

        if (!$attendance) {
            return response()->json([
                'message' => 'Bugün kayıt yok',
                'attendance' => null
            ], 200);
        }

        return response()->json([
            'message' => 'Bugünkü kayıt bulundu',
            'attendance' => [
                'check_in_time' => $attendance->check_in_time ? $attendance->check_in_time->format('H:i') : null,
                'check_out_time' => $attendance->check_out_time ? $attendance->check_out_time->format('H:i') : null
            ]
        ], 200);
    }

    public function hasShiftCheck(Request $request)
    {
        $userId = $request->input('user_id');
        $today = Carbon::now()->format('Y-m-d');

        // shift_logs tablosunda "user_id" + "shift_date=today" + is_on_shift=1 => mesai var mı?
        $hasShift = ShiftLog::where('user_id', $userId)
            ->whereDate('shift_date', $today)
            ->where('is_on_shift', 1)
            ->whereNull('exit_time') // çıkış saati yok => mesai devam
            ->exists();

        // Varsa => döndür
        return response()->json([
            'has_shift' => $hasShift,
            'message' => $hasShift ? 'Mesainiz var' : 'Mesai yok'
        ]);
    }


    private function calculateDistance($latFrom, $lonFrom, $latTo, $lonTo)
    {
        $earthRadius = 6371000;
        $latFromRad = deg2rad($latFrom);
        $lonFromRad = deg2rad($lonFrom);
        $latToRad = deg2rad($latTo);
        $lonToRad = deg2rad($lonTo);

        $latDelta = $latToRad - $latFromRad;
        $lonDelta = $lonToRad - $lonFromRad;
        $a = sin($latDelta / 2) ** 2 +
            cos($latFromRad) * cos($latToRad) *
            sin($lonDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        return $distance;
    }
}
