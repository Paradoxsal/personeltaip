<?php

namespace App\Http\Controllers;

use App\Models\UserHour;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class UserHourController extends Controller
{
    public function index()
    {
        // Tüm user_hours kayıtlarını çek, user ile birlikte
        $items = UserHour::with('user')->orderBy('id','desc')->get();
        // Tüm kullanıcılar (select2’de göstermek için)
        $users = User::orderBy('name')->get();

        return view('user_hours.index', compact('items','users'));
    }

    public function store(Request $request)
    {
        // Validasyon
        $request->validate([
            'user_id'     => 'required|integer',
            'morning_start_time'  => 'nullable|date_format:H:i', // HH:MM
            'morning_end_time'    => 'nullable|date_format:H:i',
            'evening_start_time'    => 'nullable|date_format:H:i',
            'evening_end_time'    => 'nullable|date_format:H:i',
        ]);

        try {
            UserHour::create($request->all());
        } catch (QueryException $ex) {
            return redirect()->back()->withErrors([
                'error' => 'Kayıt eklenemedi: '.$ex->getMessage()
            ]);
        }

        return redirect()->back()->with('status','Kayıt eklendi!');
    }

    public function update(Request $request, $id)
    {
        $uh = UserHour::findOrFail($id);

        $request->validate([
            'user_id'     => 'required|integer',
            'morning_start_time'  => 'nullable|date_format:H:i', // HH:MM
            'morning_end_time'    => 'nullable|date_format:H:i',
            'evening_start_time'    => 'nullable|date_format:H:i',
            'evening_end_time'    => 'nullable|date_format:H:i',
        ]);

        try {
            $uh->update($request->all());
        } catch (QueryException $ex) {
            return redirect()->back()->withErrors([
                'error' => 'Kayıt güncellenemedi: '.$ex->getMessage()
            ]);
        }

        return redirect()->back()->with('status','Kayıt güncellendi!');
    }

    public function destroy($id)
    {
        $uh = UserHour::findOrFail($id);
        $uh->delete();

        return redirect()->back()->with('status','Kayıt silindi!');
    }

    public function show($id)
    {
        // resource da var ama kullanmayabiliriz
    }

    public function create() {}
    public function edit($id) {}
}
