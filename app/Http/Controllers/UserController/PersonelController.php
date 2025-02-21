<?php

namespace App\Http\Controllers\UserController;

use App\Models\User;
use App\Http\Controllers\UserController\Auth;
use App\Models\Units;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\Personel;
use App\Http\Controllers\UserController\Hash;
class PersonelController extends Controller
{
    public function create(Request $request)
    {
        $attributes = $request->validate([
            'name' => ['required', 'max:50'],
            'password' => ['required', 'min:5', 'max:20'],
            'unit_id' => ['nullable', 'max:50'], // departments tablosu ile ilişkili.
        ]);

        $existingUser = User::where('name', $attributes['name'])->first();

        if ($existingUser) {
            return back()->withErrors(['name' => 'Bu kullanıcı adı zaten alınmış.'])->withInput();
        }

        try {
            // Şifreyi hash'le
            $attributes['password'] = \Hash::make($attributes['password']);

            // Yeni kullanıcıyı veritabanına kaydet
            User::create($attributes);

            // Başarı mesajı
            return redirect()->route('dashboard')->with('success', 'Kullanıcı başarıyla kaydedildi.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Bir sorun oluştu: ' . $e->getMessage()])->withInput();
        }
    }
    public function showProfile()
    {

        // Giriş yapmış kullanıcıyı al
        $user = \Auth::user();
        $personel = User::find($user->id);
        $department = Units::find($personel->units_id); 
        $departments = Units::all();

        // Elde edilen verileri view'e gönder
        return view('laravel-examples.user-profile', compact('personel', 'department', 'departments'));
    }


    public function updateProfile(Request $request)
    {
        // Giriş yapmış kullanıcının bilgilerini alıyoruz
        $user = \Auth::user();

        // Gelen verileri doğruluyoruz
        $validated = $request->validate([
            'name' => ['nullable', 'max:50'],
            'email' => ['nullable', 'email', 'max:50'],
            'password' => ['nullable', 'min:6', 'confirmed'],
            'phone' => ['nullable', 'max:50'],
            'units_id' => ['nullable', 'max:50'],
            'check_in_location' => ['nullable', 'max:510'],
            'check_out_location' => ['nullable', 'max:510'],
        ]);

        // Şifre güncellemesi
        if ($request->filled('password')) {
            $user->password = \Hash::make($request->password);
        } else {
            $user->password = $request->old_password;
        }

        // Kullanıcıyı güncelle
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'units_id' => $request->units_id,
            'check_in_location' => $request->check_in_location,     // location verisini check_in_location'a kaydediyor
            'check_out_location' => $request->check_out_location,    // location verisini check_out_location'a kaydediyor
            'password' => $user->password,
        ]);

        // Personel tablosunda units_id güncellemesi
        if ($request->has('units_id') && $personel = Personel::where('id', $user->id)->first()) {
            $personel->update([
                'units_id' => $request->units_id,
            ]);
        }

        session()->flash('success', 'Profil başarıyla güncellendi.');
        return redirect()->route('userprofile');
    }

}
