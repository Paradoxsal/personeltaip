<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class SystemSettingController extends Controller
{
    public function index()
    {
        // Tüm kayıtları çek
        $items = SystemSetting::orderBy('id','desc')->get();
        return view('system_settings.index', compact('items'));
    }

    public function store(Request $request)
    {
        // setting_type => required: in:entry_exit,new_version
        // eğer entry_exit ise start_time,end_time => date_format:H:i
        // eğer new_version ise version_link vb.
        $request->validate([
            'setting_type'  => 'required|in:entry_exit,new_version',
            'morning_start_time'    => 'nullable|date_format:H:i',
            'morning_end_time'      => 'nullable|date_format:H:i',
            'evening_start_time'      => 'nullable|date_format:H:i',
            'evening_end_time'      => 'nullable|date_format:H:i',
            'version_link'  => 'nullable|string|max:255',
            'version_desc'  => 'nullable|string',
            'version_status'=> 'nullable|in:send,wait', 
                // "send" => gönder, "wait" => beklet
        ]);

        try {
            SystemSetting::create($request->all());
        } catch (QueryException $ex) {
            return redirect()->back()
                ->withErrors(['error' => 'Kayıt eklenemedi: '.$ex->getMessage()]);
        }

        return redirect()->back()->with('status','Kayıt eklendi!');
    }

    public function update(Request $request, $id)
    {
        $ss = SystemSetting::findOrFail($id);

        $request->validate([
            'setting_type'  => 'required|in:entry_exit,new_version',
            'morning_start_time'    => 'nullable|date_format:H:i',
            'morning_end_time'      => 'nullable|date_format:H:i',
            'evening_start_time'      => 'nullable|date_format:H:i',
            'evening_end_time'      => 'nullable|date_format:H:i',
            'version_link'  => 'nullable|string|max:255',
            'version_desc'  => 'nullable|string',
            'version_status'=> 'nullable|in:send,wait',
        ]);

        try {
            $ss->update($request->all());
        } catch (QueryException $ex) {
            return redirect()->back()
                ->withErrors(['error' => 'Kayıt güncellenemedi: '.$ex->getMessage()]);
        }

        return redirect()->back()->with('status','Kayıt güncellendi!');
    }

    public function destroy($id)
    {
        $ss = SystemSetting::findOrFail($id);
        $ss->delete();

        return redirect()->back()->with('status','Kayıt silindi!');
    }

    // resource içinde ama kullanmadık:
    public function create(){}
    public function show($id){}
    public function edit($id){}
}
