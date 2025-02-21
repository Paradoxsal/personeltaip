<?php

namespace App\Http\Controllers;

use App\Models\ShiftLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class ShiftLogController extends Controller
{
    public function index()
    {
        $logs = ShiftLog::with(['user'])->orderBy('shift_date','desc')->get();
        $users = User::orderBy('name')->get();

        return view('shift.index', compact('logs','users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'        => 'required|integer',
            'shift_date'     => 'required|date',
            'is_on_shift'    => 'required|boolean',
            'no_shift_reason'=> 'nullable|string|max:255',
            'exit_time'      => 'nullable|date',
        ]);

        try {
            // 1) Kaydı oluştur
            $log = ShiftLog::create($request->all());

            // 2) User tablosunda shift=1 (veya 0) ayarla
            $user = User::findOrFail($request->user_id);
            if ($request->is_on_shift) {
                // mesaiye kalacak => shift=1
                $user->shift = 1;
            } else {
                // mesaiye kalmadı => shift=0
                $user->shift = 0;
            }
            $user->save();

        } catch (QueryException $ex) {
            return redirect()->back()->withErrors([
                'error'=>'Mesai kaydı eklenemedi => '.$ex->getMessage()
            ]);
        }

        return redirect()->back()->with('status','Mesai kaydı eklendi!');
    }

    public function update(Request $request, $id)
    {
        $shift = ShiftLog::findOrFail($id);

        $request->validate([
            'user_id'        => 'required|integer',
            'shift_date'     => 'required|date',
            'is_on_shift'    => 'required|boolean',
            'no_shift_reason'=> 'nullable|string|max:255',
            'exit_time'      => 'nullable|date',
        ]);

        try {
            $shift->update($request->all());

            // User shift sütununu da güncelle
            $user = User::findOrFail($request->user_id);
            if ($request->is_on_shift) {
                $user->shift = 1;
            } else {
                $user->shift = 0;
            }
            $user->save();

        } catch (QueryException $ex) {
            return redirect()->back()->withErrors([
                'error'=>'Mesai kaydı güncellenemedi => '.$ex->getMessage()
            ]);
        }

        return redirect()->back()->with('status','Mesai kaydı güncellendi!');
    }

    public function destroy($id)
    {
        $shift = ShiftLog::findOrFail($id);

        // Optionally => user shift=0? 
        // eğer silerken user->shift=0 yapmak isterseniz
        // $user = $shift->user; 
        // if($user) {
        //   $user->shift=0; 
        //   $user->save(); 
        // }

        $shift->delete();

        return redirect()->back()->with('status','Mesai kaydı silindi!');
    }
}
