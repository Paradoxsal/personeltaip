<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WeekendControl;
use App\Models\User;
use Carbon\Carbon;

class WeekendControlController extends Controller
{
    /**
     * Geçerli hafta için ayarı döndüren statik fonksiyon.
     * 
     * Eğer bugün hafta sonuysa:
     *   - Hesaplanan week_start_date, bugünün hafta sonu ise "next monday" olarak belirlenir.
     *   - DB'den, sadece bu hesaplanan güncel hafta tarihine (ör. 2025-03-03) sahip,
     *     kullanıcıya özel ya da global ayar aranır.
     *   - Eğer kayıt bulunursa ve weekend_active true ise, bu hafta için hafta sonu mesai (erişim serbest) kabul edilir.
     *   - Kayıt bulunamazsa ya da kayıt mevcut fakat weekend_active false ise, erişim engellenir.
     * 
     * Eğer bugün hafta sonu değilse, bu kontrol uygulanmaz (false döner).
     *
     * Not: Sadece tam eşleşen (yani geçerli haftaya ait) kayıt dikkate alınır. Geçmiş veya çok ileri tarihli kayıtlar
     * sorguya dahil edilmez.
     *
     * @param User $user
     * @return bool
     */
    public static function isWeekendActiveForUser(User $user): bool
    {
        $today = Carbon::today();
        if (!$today->isWeekend()) {
            // Bugün hafta sonu değilse, hafta sonu kontrolü uygulanmaz.
            return false;
        }

        // Eğer bugün hafta sonuysa, geçerli hafta için week_start_date "next monday" olarak belirlenir.
        $weekStartDate = Carbon::parse('next monday')->toDateString();

        // Sorgulamada sadece güncel haftaya ait kayıt aranır.
        // Böylece, eğer DB kaydı mevcutsa, kesinlikle week_start_date değeri hesaplanan güncel hafta (ör. 2025-03-03) ile
        // eşleşir. Eğer kayıt eski (geçmiş) veya çok ileri bir tarihse sorguya dahil olmaz.
        $userControl = WeekendControl::where('week_start_date', $weekStartDate)
            ->where('user_id', $user->id)
            ->first();

        if ($userControl) {
            return (bool) $userControl->weekend_active;
        }

        $globalControl = WeekendControl::where('week_start_date', $weekStartDate)
            ->where('all_users', true)
            ->first();

        return $globalControl ? (bool) $globalControl->weekend_active : false;
    }

    /**
     * Haftasonu kontrol ayarlarının listelendiği sayfa.
     */
    public function index()
    {
        $today = Carbon::today();
        // Yönetim ekranında da güncel hafta için kayıtlar kullanılmalı.
        if ($today->isWeekend()) {
            $weekStartDate = Carbon::parse('next monday')->toDateString();
        } else {
            $weekStartDate = $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        }

        $weekendControls = WeekendControl::where('week_start_date', $weekStartDate)->get();
        $users = User::all();
        return view('weekend_check.index', compact('weekendControls', 'users'));
    }

    /**
     * Yeni haftasonu kontrol ayarını oluşturur ya da aynı haftaya ait kayıt varsa günceller.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'weekend_active' => 'required|boolean',
            'apply_for'      => 'required|in:all,specific',
            'user_id'        => 'nullable|exists:users,id',
            'week_start_date'=> 'nullable|date'
        ]);

        $applyFor = $validated['apply_for'];
        $userId = $validated['user_id'] ?? null;

        if (Carbon::today()->isWeekend()) {
            $weekStartDate = Carbon::parse('next monday')->toDateString();
        } else {
            $weekStartDate = Carbon::today()->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        }
        
        $weekendActive = $validated['weekend_active'];
        $allUsers = ($applyFor === 'all');

        if (!$allUsers && !$userId) {
            return redirect()->back()->withErrors(['user_id' => 'Belirli kullanıcı seçimi için bir kullanıcı seçmelisiniz.']);
        }

        // Aynı hafta (hesaplanan week_start_date ile) ve seçime göre kayıt varsa güncelle, yoksa oluştur.
        $control = WeekendControl::where('week_start_date', $weekStartDate)
            ->where('all_users', $allUsers)
            ->when(!$allUsers, function ($query) use ($userId) {
                return $query->where('user_id', $userId);
            })->first();

        if ($control) {
            $control->update([
                'weekend_active' => $weekendActive
            ]);
        } else {
            $control = WeekendControl::create([
                'user_id'        => $allUsers ? null : $userId,
                'all_users'      => $allUsers,
                'week_start_date'=> $weekStartDate,
                'weekend_active' => $weekendActive
            ]);
        }

        return redirect()->route('weekned-settings.index')
            ->with('status', 'Haftasonu ayarı başarıyla güncellendi.');
    }

    /**
     * Mevcut haftaya ait ayarın düzenlenmesi.
     */
    public function update(Request $request, $id)
    {
        $control = WeekendControl::findOrFail($id);

        $validated = $request->validate([
            'weekend_active' => 'required|boolean',
            'apply_for'      => 'required|in:all,specific',
            'user_id'        => 'nullable|exists:users,id',
            'week_start_date'=> 'required|date'
        ]);

        $applyFor = $validated['apply_for'];
        $userId = $validated['user_id'] ?? null;
        $weekStartDate = $validated['week_start_date'];
        $weekendActive = $validated['weekend_active'];
        $allUsers = ($applyFor === 'all');

        if (!$allUsers && !$userId) {
            return redirect()->back()->withErrors(['user_id' => 'Belirli kullanıcı seçimi için bir kullanıcı seçmelisiniz.']);
        }

        $control->update([
            'user_id'        => $allUsers ? null : $userId,
            'all_users'      => $allUsers,
            'week_start_date'=> $weekStartDate,
            'weekend_active' => $weekendActive
        ]);

        return redirect()->route('weekned-settings.index')
            ->with('status', 'Haftasonu ayarı başarıyla güncellendi.');
    }

    /**
     * Seçilen haftasonu kontrol ayarını siler.
     */
    public function destroy($id)
    {
        $control = WeekendControl::findOrFail($id);
        $control->delete();

        return redirect()->route('weekned-settings.index')
            ->with('status', 'Haftasonu ayarı silindi.');
    }

    /**
     * (Opsiyonel) API için; Geçerli haftada haftasonu ayarının aktif olup olmadığını sorgular.
     */
    public function isWeekendActive(Request $request)
    {
        $userId = $request->input('user_id');
        $today = Carbon::today();
        if ($today->isWeekend()) {
            $weekStartDate = Carbon::parse('next monday')->toDateString();
        } else {
            $weekStartDate = $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        }

        $control = WeekendControl::where('week_start_date', $weekStartDate)
            ->where(function($query) use ($userId) {
                $query->where('all_users', true)
                      ->orWhere('user_id', $userId);
            })
            ->first();

        $isActive = $control ? (bool)$control->weekend_active : false;

        return response()->json([
            'status' => 'ok',
            'weekend_active' => $isActive,
        ], 200);
    }
}
