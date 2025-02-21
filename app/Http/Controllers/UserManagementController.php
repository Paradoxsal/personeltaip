<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserDevice;

class UserManagementController extends Controller
{
    /**
     * Hem GET hem POST isteklerini tek bir metotta karşılayıp,
     * kullanıcıları listeleyen ve yönetim (ban, unban, update, vs.) işlemlerini yapan örnek.
     * 
     * 
     */
    public function index()
    {
        // Sadece banlı olanları çekmek isterseniz:
        $users = User::where('banned', 1)->get();

        // Eğer TÜM kullanıcıları istiyorsanız: 
        // $users = User::all();

        return view('users.manage', compact('users'));
    }

    public function manage(Request $request)
    {
        if ($request->isMethod('post')) {
            // POST isteği: form üzerinden gelen 'action' ve 'user_id' değerine göre işlem yapalım
            $action = $request->input('action');
            $userId = $request->input('user_id');

            $user = User::findOrFail($userId);

            if ($action === 'update') {
                // Kullanıcı bilgilerini güncelleme
                $user->name = $request->input('name');
                $user->email = $request->input('email');

                if ($request->filled('password')) {
                    // Yeni şifre girişi varsa, bunu güncelle
                    $user->password = bcrypt($request->input('password'));
                }
                $user->save();

                return redirect()->route('users.manage')->with('success', 'Kullanıcı güncellendi.');

            } elseif ($action === 'ban') {
                // Kullanıcıyı banla
                $user->banned = 1;
                $user->banned_log = 'Yönetici tarafından banlandı.';
                $user->save();

                return redirect()->route('users.manage')->with('success', 'Kullanıcı banlandı.');

            } elseif ($action === 'unban') {
                // Ban kaldır
                $user->banned = 0;
                $user->banned_log = null;

                // Cihaz ve yetkiyi de sıfırlamak istiyorsak:
                $user->device_info = null;
                $user->cihaz_yetki = 0;
                // user_devices tablosunu da silebilirsiniz (isteğe göre)
                // UserDevice::where('user_id', $user->id)->delete();

                $user->save();

                return redirect()->route('users.manage')->with('success', 'Ban kaldırıldı, cihaz bilgileri sıfırlandı.');

            } elseif ($action === 'reset-device') {
                // Cihaz bilgilerini sıfırlama
                $user->device_info = null;
                $user->cihaz_yetki = 0;
                $user->save();

                UserDevice::where('user_id', $user->id)->delete();

                return redirect()->route('users.manage')->with('success', 'Cihaz bilgileri sıfırlandı.');
            }
            // Aksi halde hiçbir işlem yapmadan geri dön
            return redirect()->route('users.manage')->with('info', 'Bir işlem yapılmadı.');
        }

        // GET isteği: Kullanıcıları listeleyip blade'e gönder
        $users = User::orderBy('id', 'ASC')->get();
        return view('users.manage', compact('users'));
    }
}
