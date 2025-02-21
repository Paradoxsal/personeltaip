<?php

namespace App\Http\Controllers\UserController;

use App\Http\Controllers\Controller;
use App\Models\Attendance; // eğer gerçekten kullanıyorsan
use App\Models\Units;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserEditController extends Controller
{
    public function index(Request $request)
    {
        // Tüm kullanıcılar
        $users = User::all();
        // Birimler
        $departments = Units::all();
        // attendances
        $attendances = Attendance::all(); // eğer yoksa kaldır

        return view('laravel-examples.usersettiginis', compact('users', 'departments', 'attendances'));
    }

    public function update(Request $request, $id)
    {
        // Kullanıcıyı bul
        $user = User::findOrFail($id);

        // Validasyon
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'phone' => 'required|string|max:255',
            'units_id' => 'required|exists:units,id',

            // Şifre doğrulama (eğer password_confirmation varsa, 'confirmed' eklenebilir)
            'password' => 'nullable|min:6',

            // attendances tablosu ile ilgili alanlar (varsa):
            'check_in_time' => 'nullable|date',
            'check_out_time' => 'nullable|date',

            // Lokasyonlar
            'check_in_location' => 'nullable|string|max:255',
            'check_out_location' => 'nullable|string|max:255',

            // role (0 veya 1) => Yetki Ver / Yetki Verme
            'role' => 'required|in:0,1',
        ]);

        // 1) Users tablosu alanlarını güncelle
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->phone = $request->input('phone');
        $user->units_id = $request->input('units_id');

        // check_in_location, check_out_location
        $user->check_in_location = $request->input('check_in_location');
        $user->check_out_location = $request->input('check_out_location');

        // Şifre boş değilse yenile
        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        // role alanını güncelle (0 veya 1)
        $user->role = $request->input('role');

        $user->save();

        // 2) attendances tablosu (varsa) 
        // (Önceki yanıttaki 1 saat geriye alma mantığını korumak istersen)
        $attendance = Attendance::firstOrCreate(['user_id' => $user->id]);

        if ($request->filled('check_in_time')) {
            $attendance->check_in_time = Carbon::parse($request->input('check_in_time'))->subHour();
        }
        if ($request->filled('check_out_time')) {
            $attendance->check_out_time = Carbon::parse($request->input('check_out_time'))->subHour();
        }
        $attendance->save();

        return redirect()->route('usersettiginis.index')
            ->with('success', 'Kullanıcı başarıyla güncellendi.');
    }


    public function destroy($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->delete();
        }
        return redirect()->route('usersettiginis.index')
            ->with('success', 'Kullanıcı başarıyla silindi.');
    }
}
