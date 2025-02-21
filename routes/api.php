<?php
// routes/api.php

use App\Http\Controllers\Api\WorkManagerApiController;
use App\Http\Controllers\CheckAllController;
use App\Http\Controllers\FakeLocationController;
use App\Http\Controllers\FcmTokenController;
use App\Http\Controllers\GeoLogController;
use App\Http\Controllers\HourController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\WorkManagerController;
use App\Models\UserFcmToken;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DeviceController;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::post('/save-location', [LocationController::class, 'store']);



Route::post('/store-fcm-token', function (Request $request) {
    // 1) Validasyon: user_id ve fcm_token zorunlu
    $request->validate([
        'user_id' => 'required|integer',
        'fcm_token' => 'required|string',
    ]);

    // 2) Kayıt veya güncelleme
    UserFcmToken::updateOrCreate(
        [
            'user_id' => $request->user_id,
            'fcm_token' => $request->fcm_token,
        ],
        [
            'updated_at' => now(),
            'created_at' => now(),
        ]
    );

    return response()->json(['message' => 'Token kaydedildi'], 200);
});

Route::post('/geo-log', [GeoLogController::class, 'store']);
Route::post('/ban-user', [LoginController::class, 'banUser'])->name('banUser'); // KONTROL

Route::post('/workmanager/command', [WorkManagerApiController::class, 'getCommand']);
Route::post('/workmanager/command/update', [WorkManagerApiController::class, 'updateCommand']);

Route::middleware(['auth:sanctum', 'token.valid'])->group(function () {
    Route::post('/check-all', [CheckAllController::class, 'checkAll']);

    Route::get('/profile', function (Request $request) {
        return response()->json([
            'message' => 'Profile bilgileri',
            'user' => $request->user(),
        ], 200);
    });

    Route::post('/get-check-in-location', [WorkManagerController::class, 'getCheckInLocation']);
    Route::post('/get-check-out-location', [WorkManagerController::class, 'getCheckOutLocation']);
    //////////
    ////WORKMANAGER BACKGROUNSERVİCE///////////////////////////////////////////////////////////////////////
    // Tüm fonksiyonlar WorkManagerController içinde
    Route::post('/daily-check', [WorkManagerController::class, 'dailyCheck']);
    Route::post('/create-daily-records', [WorkManagerController::class, 'createDailyRecords']);
    Route::post('/update-logs', [WorkManagerController::class, 'updateLogs']);
    Route::post('/store-geolocation', [WorkManagerController::class, 'storeGeolocation']);
    // GET istekleri
    Route::get('/attendance/check-in', [WorkManagerController::class, 'statusCheckIn']);
    Route::get('/attendance/check-out', [WorkManagerController::class, 'statusCheckOut']);
    // Örnek: store-situation-data => her saat workmanager_situation tablosu
    Route::post('/store-situation-data', [WorkManagerController::class, 'storeSituationData']);
    // Giriş/çıkış konumları
    Route::post('/get-check-in-location', [WorkManagerController::class, 'getCheckInLocation']);
    Route::post('/get-check-out-location', [WorkManagerController::class, 'getCheckOutLocation']);
    // is-checked-in => user attendances tablosu
    Route::post('/is-checked-in', [WorkManagerController::class, 'isCheckedIn']);
    //Mesai Kontrol
    Route::get('/attendance/overtime', [WorkManagerController::class, 'statusOvertime']);
    //ÖZEL SAAT KONTRÖLÜ
    Route::get('/has-user-hours', [WorkManagerController::class, 'hasUserHours']);
    ///WORKMANAGER GÜN KONTRÖLÜ
    Route::get('/is-today-off', [WorkManagerController::class, 'isTodayOff']);
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    Route::post('/fake-location/report', [FakeLocationController::class, 'reportFakeLocation']);

    Route::post('has-shift-check', [AttendanceController::class, 'hasShiftCheck']);

    Route::get('/entry-exit-hours', [HourController::class, 'getEntryExitHours']);

    Route::get('/attendance/today', [AttendanceController::class, 'today']); //GÜNLÜK SAAT KONTROLÜ

    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.checkin');
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.checkout');

    // Bu iki satırı ekleyin:
    Route::get('/attendance/check-in', [AttendanceController::class, 'statusCheckIn']);
    Route::get('/attendance/check-out', [AttendanceController::class, 'statusCheckOut']);

    Route::post('/device/register', [DeviceController::class, 'register'])->name('device.register');
    Route::post('/device/verify', [DeviceController::class, 'verify'])->name('device.verify');

    Route::middleware(['auth:sanctum', 'token.valid'])->post('/save-fcm-token', [FcmTokenController::class, 'saveToken']);
});
/*Route::get('/fcm-token', function () {
    $factory = (new Factory)
        ->withServiceAccount(config('services.firebase.credentials_file'));
    $messaging = $factory->createMessaging();

    $msg = CloudMessage::new()
        ->withChangedTarget('token', 'erYeHe8kQ_iajlp2QmwcDs:APA91bH-WsbyaKNIZp0DjPe4ZdrEucsx3dex0NMkrF5ZM-UZ1MKj0LA5Q6uaL47LB1O4UMtCwMTAD9fq2LPK44QWwgHFNXsQEH5B0hxQY8fbtuGu2SFxKp8') // BURAYA SENİN TOKEN
        ->withNotification([
            'title' => 'Merhaba', 
            'body'  => 'Flutter cihaza push test'
        ]);

    $sendReport = $messaging->send($msg);
    return 'Push denendi: ' . json_encode($sendReport);
});*/


