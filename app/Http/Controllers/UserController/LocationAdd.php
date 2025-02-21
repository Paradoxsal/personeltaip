<?php

namespace App\Http\Controllers\UserController;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;

class LocationAdd extends Controller
{
    /**
     * 1) Anasayfa (Lokasyonlar + Kullanıcılar)
     */
    public function index()
    {
        // Tüm lokasyonlar
        $locations = Location::all();
        // Tüm kullanıcılar
        $users = User::all();

        // location.blade.php gibi bir view'a gönderiyoruz
        return view('laravel-examples.location', compact('locations', 'users'));
    }

    /**
     * 2) Lokasyon Ekle (storeLocation)
     */
    public function storeLocation(Request $request)
    {
        $request->validate([
            'location_name'    => 'required|string|max:255',
            'location_address' => 'nullable|string|max:255',
            'created_by'       => 'nullable|string|max:255',
        ]);

        $loc = new Location();
        $loc->location_name    = $request->location_name;
        $loc->location_address = $request->location_address; // Lat,lng gelecek
        $loc->created_by       = $request->created_by;
        $loc->save();

        session()->flash('success', 'Lokasyon başarıyla eklendi.');
        return redirect()->back();
    }

    /**
     * 3) Lokasyon Güncelle (updateLocation)
     */
    public function updateLocation(Request $request, $id)
    {
        $request->validate([
            'location_name'    => 'required|string|max:255',
            'location_address' => 'nullable|string|max:255',
            'created_by'       => 'nullable|string|max:255',
        ]);

        $loc = Location::findOrFail($id);
        $loc->location_name    = $request->location_name;
        $loc->location_address = $request->location_address; // Lat,lng
        $loc->created_by       = $request->created_by;
        $loc->save();

        session()->flash('success', 'Lokasyon güncellendi.');
        return redirect()->back();
    }

    /**
     * 4) Lokasyon Sil (deleteLocation)
     */
    public function deleteLocation($id)
    {
        $loc = Location::findOrFail($id);
        $loc->delete();

        session()->flash('success', 'Lokasyon silindi.');
        return redirect()->back();
    }

    /**
     * 5) KULLANICILARA KONUM ATAMA (Senaryo Bazlı)
     *    - (1) Yeni Kullanıcı İçin Konum (Giriş/Çıkış)
     *    - (4) Kullanıcı Çıkış Yeri Değiş (Süresiz)
     *    - (5) Kullanıcı Giriş Yeri Değiş (Süresiz)
     *    - (6) Yeni Görev Yeri Akşam 17:00 Sonra Atanır
     */
    public function assignLocationToUsers(Request $request)
    {
        $request->validate([
            'scenario'       => 'required|in:1,4,5,6',    // Bir kerelik senaryolar (2,3) iptal
            'location_id'    => 'required|exists:locations,id',
            'selected_users' => 'required|array',
        ]);

        $scenario   = $request->scenario;
        $locationId = $request->location_id;
        $userIds    = $request->selected_users; // array
        $location   = Location::findOrFail($locationId);

        // location_name = metinsel ad,
        // location_address = lat,lng
        // Seçilen kullanıcı ID'lerini "locations.users_id" alanına virgüllü eklemek istiyorsanız:
        foreach ($userIds as $uid) {
            $user = User::find($uid);
            if (!$user) {
                continue;
            }

            // "locations" tablosundaki users_id alanına bu user'ı ekle (virgül parse)
            $existingIds = $location->users_id ? explode(',', $location->users_id) : [];
            if (!in_array($uid, $existingIds)) {
                $existingIds[] = $uid;
            }
            $location->users_id = implode(',', array_unique($existingIds));
            $location->save();

            // Senaryoya göre user tablosundaki check_in_location / check_out_location doldur
            switch ($scenario) {
                case '1':
                    // (1) Yeni Kullanıcı İçin Konum => hem giriş hem çıkış
                    // location_address = lat,lng formatı
                    $user->check_in_location  = $location->location_address;
                    $user->check_out_location = $location->location_address;
                    $user->save();
                    break;

                case '4':
                    // (4) Kullanıcı Çıkış Yeri Değiş (Süresiz)
                    $user->check_out_location = $location->location_address;
                    $user->save();
                    break;

                case '5':
                    // (5) Kullanıcı Giriş Yeri Değiş (Süresiz)
                    $user->check_in_location = $location->location_address;
                    $user->save();
                    break;

                case '6':
                    // (6) Yeni Görev Yeri Akşam 17:00 Sonra Atanır (örnek)
                    // Şimdilik hemen kaydediyoruz; gerçek hayatta cron job vs. gerekebilir
                    $user->check_in_location  = $location->location_address;
                    $user->check_out_location = $location->location_address;
                    $user->save();
                    break;
            }
        }

        session()->flash('success', 'Konum atama işlemi başarıyla uygulandı.');
        return redirect()->back();
    }

    /**
     * 6) Bu Konumdaki Kullanıcıları Göster (opsiyonel - AJAX ile)
     */
    public function showUsersInLocation(Request $request)
    {
        $locationId = $request->query('location_id');
        $loc = Location::find($locationId);
        if (!$loc) {
            return response()->json([]);
        }

        // users_id = "1,2,3"
        $userIds = $loc->users_id ? explode(',', $loc->users_id) : [];
        $users = User::whereIn('id', $userIds)->get();
        // JSON dön
        return response()->json($users);
    }
}
