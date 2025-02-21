<?php

namespace App\Http\Controllers;

use App\Models\HalfdayRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class HalfdayRequestController extends Controller
{
    /**
     * Tüm izin kayıtlarını listeler.
     */
    public function index()
    {
        $requests = HalfdayRequest::with('user')->orderBy('created_at', 'desc')->get();
        $users    = User::all();

        return view('halfday.index', compact('requests', 'users'));
    }

    /**
     * Yeni izin kaydı oluşturur.
     */
    public function store(Request $request)
    {
        // Validation kuralları
        $rules = [
            'user_id' => 'required|integer',
            'date'    => 'required|date',
            'type'    => 'required|in:morning,afternoon,full_day,rapor',
        ];

        // Tür'e göre ek kurallar
        switch ($request->input('type')) {
            case 'morning':
            case 'afternoon':
                $rules['rapor_file'] = 'prohibited';  // Dosya yüklenemez
                $rules['days_count'] = 'nullable|integer';
                // reason nullable olsa bile biz elle dolduracağız (Sabah/Öğleden Sonra)
                $rules['reason']     = 'nullable';
                break;

            case 'full_day':
                $rules['rapor_file'] = 'prohibited';
                $rules['days_count'] = 'nullable|integer';
                $rules['reason']     = 'nullable';
                break;

            case 'rapor':
                $rules['rapor_file'] = 'nullable|file|mimes:jpg,png,jpeg,gif';
                $rules['days_count'] = 'nullable|integer';
                $rules['reason']     = 'required';
                break;
        }

        // Validate
        $validated = $request->validate($rules);

        // Kullanıcı var mı?
        $user = User::find($validated['user_id']);
        if (!$user) {
            return redirect()->back()->withErrors([
                'error' => 'Girilen user_id veritabanında yok!'
            ]);
        }

        // Eğer aktif izni bitmemişse engelle
        // (end_date >= bugün)
        $aktifIzin = HalfdayRequest::where('user_id', $validated['user_id'])
            ->where('end_date', '>=', Carbon::today()->format('Y-m-d'))
            ->first();
        if ($aktifIzin) {
            return redirect()->back()->withErrors([
                'error' => 'Bu kullanıcının hâlâ devam eden bir izni var. Bitmeden yeni eklenemez!'
            ]);
        }

        // Sabah/öğleden sonra => bugün mü? Saat kontrolü
        $today       = Carbon::today()->format('Y-m-d');
        $requestDate = Carbon::parse($validated['date'])->format('Y-m-d');
        if ($requestDate === $today) {
            $nowHour = Carbon::now()->hour;
            if ($validated['type'] === 'morning' && $nowHour >= 12) {
                return redirect()->back()->withErrors([
                    'error' => 'Saat 12:00 geçtiği için bugüne sabah izni verilemez!'
                ]);
            }
            if ($validated['type'] === 'afternoon' && $nowHour >= 14) {
                return redirect()->back()->withErrors([
                    'error' => 'Saat 14:00 geçtiği için bugüne öğleden sonra izni verilemez!'
                ]);
            }
        }

        // Dosya (rapor) yükleme
        $raporPath = null;
        if ($request->hasFile('rapor_file')) {
            $originalName = $request->file('rapor_file')->getClientOriginalName();
            $filename     = time() . '_' . $originalName;
            $request->file('rapor_file')->move(public_path('images'), $filename);
            $raporPath    = $filename;
        }

        // reason & days_count
        $reason     = $validated['reason'] ?? null;
        $days_count = (int)($validated['days_count'] ?? 0);

        // Eğer sabah veya öğleden sonra seçildiyse, reason otomatik doldur
        if ($validated['type'] === 'morning') {
            $reason = 'Sabah İzni';
        } elseif ($validated['type'] === 'afternoon') {
            $reason = 'Öğleden Sonra İzni';
        }

        // end_date = date + days_count
        $startDate = Carbon::parse($validated['date']);
        $endDate   = $startDate->copy()->addDays($days_count);

        // Kaydet
        try {
            HalfdayRequest::create([
                'user_id'    => $validated['user_id'],
                'date'       => $validated['date'],
                'type'       => $validated['type'],
                'reason'     => $reason,
                'days_count' => $days_count,
                'rapor_file' => $raporPath,
                'status'     => 'pending',
                'end_date'   => $endDate->format('Y-m-d'),
            ]);
        } catch (QueryException $ex) {
            // MySQL error codes
            $errorCode = $ex->errorInfo[1] ?? null;
            if ($errorCode === 1062) {
                return redirect()->back()->withErrors([
                    'error' => 'Aynı kullanıcı ve tarihe tekrar izin eklenemez!'
                ]);
            } elseif ($errorCode === 1452) {
                return redirect()->back()->withErrors([
                    'error' => 'Girilen user_id veritabanında yok (FK hatası)!'
                ]);
            } else {
                return redirect()->back()->withErrors([
                    'error' => 'Veritabanı hatası: ' . $ex->getMessage()
                ]);
            }
        }

        return redirect()->back()->with('status', 'Yeni izin kaydedildi!');
    }

    /**
     * Var olan izni günceller.
     */
    public function update(Request $request, $id)
    {
        $izin = HalfdayRequest::findOrFail($id);

        // Validation
        $rules = [
            'type' => 'required|in:morning,afternoon,full_day,rapor',
            'date' => 'required|date',
        ];

        switch ($request->input('type')) {
            case 'morning':
            case 'afternoon':
                $rules['rapor_file'] = 'prohibited';
                $rules['days_count'] = 'nullable|integer';
                $rules['reason']     = 'nullable';
                break;
            case 'full_day':
                $rules['rapor_file'] = 'prohibited';
                $rules['days_count'] = 'nullable|integer';
                $rules['reason']     = 'nullable';
                break;
            case 'rapor':
                $rules['rapor_file'] = 'nullable|file|mimes:jpg,png,jpeg,gif';
                $rules['days_count'] = 'nullable|integer';
                $rules['reason']     = 'required';
                break;
        }

        $validated = $request->validate($rules);

        // Dosya güncellemesi (sadece rapor'da)
        if ($request->hasFile('rapor_file')) {
            if ($izin->rapor_file) {
                $oldPath = public_path('images').DIRECTORY_SEPARATOR.$izin->rapor_file;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $originalName = $request->file('rapor_file')->getClientOriginalName();
            $filename = time() . '_' . $originalName;
            $request->file('rapor_file')->move(public_path('images'), $filename);
            $validated['rapor_file'] = $filename;
        }

        // Sabah / Öğleden sonra => reason override
        if ($validated['type'] === 'morning') {
            $validated['reason'] = 'Sabah İzni';
        } elseif ($validated['type'] === 'afternoon') {
            $validated['reason'] = 'Öğleden Sonra İzni';
        }

        // Gün sayısı farkı => end_date'i güncelle
        $oldDays = (int)$izin->days_count;
        $newDays = (int)($validated['days_count'] ?? 0);
        $diff    = $newDays - $oldDays;

        $oldEndDate = Carbon::parse($izin->end_date);
        $newEndDate = $oldEndDate->copy()->addDays($diff);

        $validated['end_date'] = $newEndDate->format('Y-m-d');

        try {
            $izin->update($validated);
        } catch (QueryException $ex) {
            $errorCode = $ex->errorInfo[1] ?? null;
            if ($errorCode === 1062) {
                return redirect()->back()->withErrors([
                    'error' => 'Aynı kullanıcı ve tarihe tekrar izin eklenemez (update)!'
                ]);
            } elseif ($errorCode === 1452) {
                return redirect()->back()->withErrors([
                    'error' => 'Girilen user_id veritabanında yok! (FK hatası) [update]'
                ]);
            } else {
                return redirect()->back()->withErrors([
                    'error' => 'Veritabanı hatası (update): ' . $ex->getMessage()
                ]);
            }
        }

        return redirect()->back()->with('status', 'İzin güncellendi!');
    }

    /**
     * İzni siler.
     */
    public function destroy($id)
    {
        $izin = HalfdayRequest::findOrFail($id);

        // Varsa rapor dosyası sil
        if ($izin->rapor_file) {
            $oldPath = public_path('images').DIRECTORY_SEPARATOR.$izin->rapor_file;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $izin->delete();

        return redirect()->back()->with('status', 'İzin silindi!');
    }
}
