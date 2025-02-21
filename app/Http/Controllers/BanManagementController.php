<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserDevice;

class BanManagementController extends Controller
{
    // GET: /ban-management
    public function index()
    {
        // Tüm kullanıcıları çekip listeleyelim.
        // Dilerseniz sadece banlıları çekmek isterseniz: where('banned',1)->get()
        $users = User::orderBy('id','ASC')->get();
        return view('ban.index', compact('users'));
    }

    // POST: /ban-management/update
    // Düzenleme modalından gelen verileri burada işleyelim.
    public function update(Request $request)
    {
        $request->validate([
            'user_id'        => 'required|integer',
            'banned'         => 'required|boolean',
            'ban_reason'     => 'nullable|string',
            'cihaz_yetki'    => 'required|boolean',
            'device_info'    => 'nullable|string',  // Disabled input, genelde formda geliyor
            'device_option'  => 'nullable|string',  // "new_device", "old_device" vs.
            'old_device_info'=> 'nullable|string',  // Kullanıcı eğer "ban_kaldir_eski" seçerse, buraya yazacak
        ]);

        $user = User::findOrFail($request->user_id);

        // 1) Ban durumu ve sebebi
        if ($request->banned) {
            $user->banned = 1;
            $user->banned_log = $request->ban_reason ?? 'Banlandı';
        } else {
            $user->banned = 0;
            $user->banned_log = null;
        }

        // 2) Cihaz yetkisi
        $user->cihaz_yetki = $request->cihaz_yetki ? 1 : 0;

        // 3) device_info güncelleme seçeneği
        // device_option'a göre farklı mantık:
        //   - "keep_current" => Mevcut device_info aynen kalsın
        //   - "new_device" => Sıfır device_info ver (null) veya yenisini ata
        //   - "ban_kaldir_eski" => old_device_info alanına girilen değeri ata
        switch ($request->device_option) {
            case 'new_device':
                // Tamamen sıfırlıyoruz (ya da "brand_model_xxx" vb. el ile atayabilirsiniz)
                $user->device_info = null;
                // UserDevice tablosunda da kayıt silmek isterseniz:
                UserDevice::where('user_id', $user->id)->delete();
                break;

            case 'ban_kaldir_eski':
                // Formda old_device_info alanına girilen değeri al
                if ($request->old_device_info) {
                    $user->device_info = $request->old_device_info;
                    // user_devices tablosunda da kaydı güncelleyebilirsiniz
                    // Eski kaydı yoksa "create", varsa "update"
                    $device = UserDevice::firstOrNew([
                        'user_id' => $user->id,
                        'device_info' => $request->old_device_info,
                    ]);
                    $device->cihaz_yetki = $user->cihaz_yetki;
                    $device->save();
                }
                break;

            default:
                // keep_current
                // $user->device_info olduğu gibi kalıyor
                break;
        }

        $user->save();

        return redirect()->back()->with('success', 'Kullanıcı güncellendi.');
    }
}
