<?php

namespace App\Http\Controllers;

use App\Models\ManualNotification;
use App\Models\User;
use App\Models\Unit;  // "units" tablosunun modeli
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Units;

class ManualNotificationController extends Controller
{
    public function index()
    {
        // 1) Tüm bildirimleri al
        $notifications = ManualNotification::orderBy('id','desc')->get();

        // 2) "Tek Kullanıcı" seçeneği için tüm kullanıcılar
        $allUsers = User::select('id','name')->get();

        // 3) "Birim" seçeneği için units tablosu
        $allUnits = Units::select('id','unit_name')->get();

        // 4) Blade’e gönder
        return view('notifications.index', compact('notifications','allUsers','allUnits'));
    }

    public function store(Request $request)
    {
        // Validasyon: title/body yalnızca "action=push" ise required olacak.
        // "action=data" (konum bildirimi) ise title/body gerekmiyor.
        // => Bunu "bazı alanlar opsiyonel" mantığıyla yapabiliriz.
        
        $action = $request->input('action'); // "push" veya "data"
        
        $rules = [
            'action'       => 'required|string', // push|data
            'target_type'  => 'required|string', // all|user|group
            'scheduled_at' => 'nullable|date',
            'selected_user_id' => 'nullable|integer',
            'selected_unit_id' => 'nullable|integer',
        ];

        if ($action === 'push') {
            // Normal bildirim: title is required
            $rules['title'] = 'required|string';
            $rules['body']  = 'nullable|string';
        } else {
            // action === 'data': konum bildirimi
            // title/body gerek yok => optional
            $rules['title'] = 'nullable|string';
            $rules['body']  = 'nullable|string';
        }

        $data = $request->validate($rules);

        // Yeni bildirim
        $notif = new ManualNotification();
        
        // "action" alanı veritabanında (migrations) olmalı: string
        $notif->action       = $data['action'];  // push veya data
        $notif->target_type  = $data['target_type'];
        $notif->status       = 'pending';
        $notif->scheduled_at = $data['scheduled_at'] ?? null;

        if ($data['action'] === 'push') {
            // Normal bildirim => title/body kaydet
            $notif->title = $data['title'];
            $notif->body  = $data['body'] ?? null;
        } else {
            // data (konum) => title/body dolu olmayabilir
            $notif->title = null;
            $notif->body  = null;
        }

        // target_type'ye göre user_id set ediliyor
        if ($data['target_type'] === 'all') {
            $notif->user_id = null; 
        }
        elseif ($data['target_type'] === 'user') {
            $notif->user_id = (string) ($data['selected_user_id'] ?? '');
        }
        elseif ($data['target_type'] === 'group') {
            $unitId = $data['selected_unit_id'];
            $usersInUnit = User::where('units_id', $unitId)->pluck('id')->toArray();
            $notif->user_id = implode(',', $usersInUnit);
        }

        $notif->save();

        return redirect()
            ->route('notifications.index')
            ->with('success','Bildirim başarıyla oluşturuldu.');
    }

    public function update(Request $request, ManualNotification $notification)
    {
        // Benzer mantık: action=push => title required, action=data => optional
        $action = $request->input('action');
        
        $rules = [
            'action'       => 'required|string',
            'target_type'  => 'required|string',
            'scheduled_at' => 'nullable|date',
            'selected_user_id' => 'nullable|integer',
            'selected_unit_id' => 'nullable|integer',
        ];

        if ($action === 'push') {
            $rules['title'] = 'required|string';
            $rules['body']  = 'nullable|string';
        } else {
            $rules['title'] = 'nullable|string';
            $rules['body']  = 'nullable|string';
        }

        $data = $request->validate($rules);

        $notification->action       = $data['action'];
        $notification->target_type  = $data['target_type'];
        $notification->scheduled_at = $data['scheduled_at'] ?? null;
        $notification->status       = 'pending'; // güncellendi => pending

        if ($data['action'] === 'push') {
            $notification->title = $data['title'];
            $notification->body  = $data['body'] ?? null;
        } else {
            // data => konum
            $notification->title = null;
            $notification->body  = null;
        }

        if ($data['target_type'] === 'all') {
            $notification->user_id = null;
        } elseif ($data['target_type'] === 'user') {
            $notification->user_id = (string) ($data['selected_user_id'] ?? '');
        } elseif ($data['target_type'] === 'group') {
            $usersInUnit = User::where('units_id', $data['selected_unit_id'])->pluck('id')->toArray();
            $notification->user_id = implode(',', $usersInUnit);
        }

        $notification->save();

        return redirect()
            ->route('notifications.index')
            ->with('success','Bildirim güncellendi.');
    }

    public function destroy(ManualNotification $notification)
    {
        $notification->delete();
        return redirect()
            ->route('notifications.index')
            ->with('success','Bildirim silindi.');
    }
}
