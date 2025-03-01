<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BanManagementController;
use App\Http\Controllers\BirimController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\HalfdayRequestController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\ManualNotificationController;
use App\Http\Controllers\PhoneSettingsController;
use App\Http\Controllers\ShiftLogController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\UserController\DashboardController;
use App\Http\Controllers\UserController\LocationAdd;
use App\Http\Controllers\UserController\PersonelController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\UserController\UserEditController;
use App\Http\Controllers\UserHourController;
use App\Http\Controllers\WeekendControlController;
use Illuminate\Support\Facades\Route;
use App\Events\LocationUpdatedEvent;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
*/

Route::get('/realtime-locations', function () {
    return view('realtime_locations');
});

Route::get('/test', function () {
    broadcast(new LocationUpdatedEvent(1, 41.0082, 28.9784))->toOthers();
    return "Event tetiklendi!";
});

Route::get('/cron-run/{token}', [CronController::class, 'run']);

Route::group(['middleware' => 'auth'], function () {

    Route::get('/', [HomeController::class, 'home']);  // Anasayfa

    Route::get('/dashboard', [DashboardController::class, 'Dashboard'])->name('dashboard')->middleware('auth'); //Dashboard

    Route::resource('notifications', ManualNotificationController::class);

    Route::resource('halfday', HalfdayRequestController::class);

    Route::resource('holidays', HolidayController::class);

    Route::resource('shift-logs', ShiftLogController::class);

    Route::resource('user-hours', UserHourController::class);

    Route::resource('system-settings', SystemSettingController::class);

    Route::resource('weekned-settings', WeekendControlController::class);

    /*Route::match(['get', 'post'], '/users-manage', [UserManagementController::class, 'manage'])
        ->name('users.manage');*/
    Route::get('/audit-logs', [AuditLogController::class, 'index']);


    Route::get('/updateProfile', [PersonelController::class, 'updateProfile'])->name('updateProfile')->middleware('auth');
    Route::get('/userprofile', [PersonelController::class, 'showProfile'])->name('showProfile')->middleware('auth');
    Route::post('userprofile', [PersonelController::class, 'userprofile'])->name('userprofile')->middleware('auth');
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    Route::get('/ban-management', [BanManagementController::class, 'index'])->name('ban.index');
    // form POST -> update
    Route::post('/ban-management/update', [BanManagementController::class, 'update'])->name('ban.update');

    // Lokasyon anasayfa
    Route::get('/location', [LocationAdd::class, 'index'])->name('location');
    // Lokasyon CRUD
    Route::post('/location/store', [LocationAdd::class, 'storeLocation'])->name('location_add.storeLocation');
    Route::put('/location/update/{id}', [LocationAdd::class, 'updateLocation'])->name('location_add.updateLocation');
    Route::delete('/location/delete/{id}', [LocationAdd::class, 'deleteLocation'])->name('location_add.deleteLocation');
    // Kullanıcılara konum atama (senaryo)
    Route::post('/location/assign', [LocationAdd::class, 'assignLocationToUsers'])->name('location_add.assignLocationToUsers');
    // Bu Konumdaki Kullanıcıları Göster (AJAX)
    Route::get('/location/showUsersInLocation', [LocationAdd::class, 'showUsersInLocation'])
        ->name('location_add.showUsersInLocation');
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////Anasayfa Düzenleme Ve Kullanıcı Ekleme

    Route::post('/personel.create', [PersonelController::class, 'create'])->name('personel.create')->middleware('auth');

    // Profil düzenleme sayfası
    Route::resource('usersettiginis', UserEditController::class);

    Route::resource('birimsettiginis', BirimController::class);
    //////////////////////////////////////////////////////////////////
// Telefon Ayarları Sayfası
    Route::get('/phone-settings', [PhoneSettingsController::class, 'index'])->name('phoneSettings.index');
    // Güncelleme
    Route::put('/phone-settings/{userId}', [PhoneSettingsController::class, 'update'])->name('phoneSettings.update');
    //////////////////////////////////////////////////////////////////
    Route::get('static-sign-in', function () { // Login Ekranı
        return view('static-sign-in');
    })->name('sign-in');

    /*Route::get('static-sign-up', function () {
        return view('static-sign-up');
    })->name('sign-up');*/

    Route::get('/login', function () {
        return view('dashboard');
    })->name('sign-up');
    Route::get('/logout', [SessionsController::class, 'destroy']); // Çıkış İşlemleri
});

Route::group(['middleware' => 'guest'], function () {

    Route::post('/session', [SessionsController::class, 'store']);
});

Route::get('/login', function () {
    return view('session/login-session');
})->name('login');