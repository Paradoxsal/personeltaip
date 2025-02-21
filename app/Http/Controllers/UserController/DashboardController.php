<?php

namespace App\Http\Controllers\UserController;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Units;
use App\Models\Location; // <-- Lokasyon Modeli (düzelt: eğer farklıysa)
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function Dashboard()
    {
        // 1) Departmanları al (önceki kod gibi)
        $departments = Units::all();

        // Departmanları işleyelim (bu kısım senin mevcut kodun)
        foreach ($departments as $department) {
            $birim_baskani = $department->birim_baskani ?? 'Atanmamış';
            $personel_sayisi = User::where('units_id', $department->id)->count();
            $department->personel_sayisi = $personel_sayisi;

            $last_updated_user = User::where('units_id', $department->id)
                ->orderBy('updated_at', 'desc')
                ->first();
            $department->last_updated_at = $last_updated_user
                ? $last_updated_user->updated_at->format('Y/m/d')
                : 'N/A';
        }

        // 2) İstenen sayıları hesapla:
        // Toplam konum (locations tablosu kayıt sayısı)
        $toplamKonum = Location::count();

        // Toplam kullanıcı sayısı (users tablosu)
        $toplamKullaniciSayisi = User::count();

        // Toplam birim sayısı (units tablosu)
        $toplamBirimSayisi = Units::count();

        // Yetkili Kişi Sayısı (super = 1 olan user'lar)
        $yetkiliKisiSayisi = User::where('role', 1)->count();

        // 3) View'a gönder
        return view('dashboard', [
            'departments' => $departments,
            'toplamKonum' => $toplamKonum,
            'toplamKullaniciSayisi' => $toplamKullaniciSayisi,
            'toplamBirimSayisi' => $toplamBirimSayisi,
            'yetkiliKisiSayisi' => $yetkiliKisiSayisi,
        ]);
    }
}
