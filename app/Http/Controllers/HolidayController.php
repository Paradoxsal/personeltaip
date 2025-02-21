<?php
namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class HolidayController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $holidays = Holiday::orderBy('start_date')->get();

        foreach ($holidays as $h) {
            // Tarihleri Carbon'a çevir:
            $start = Carbon::parse($h->start_date);
            $end   = Carbon::parse($h->end_date);

            // Varsayılan:
            $h->calcDays  = 0;
            $h->calcLabel = 'Bitmiş';

            // Sadece "active" olanlar için hesaplayalım
            if ($h->status === 'active') {
                if ($start->gt($today)) {
                    // (1) Henüz başlamadı => toplam gün
                    $h->calcDays  = $end->diffInDays($start) + 1;
                    $h->calcLabel = 'Toplam Gün';
                }
                elseif ($today->gt($end)) {
                    // (2) Bitmiş => 0
                    $h->calcDays  = 0;
                    $h->calcLabel = 'Bitmiş';
                }
                else {
                    // (3) İçindeyiz (start <= today <= end) => kalan gün
                    $h->calcDays  = $end->diffInDays($today) + 1;
                    $h->calcLabel = 'Kalan Gün';
                }

                // Negatif koruması
                if ($h->calcDays < 0) {
                    $h->calcDays  = 0;
                    $h->calcLabel = 'Bitmiş';
                }
            }
            else {
                // waiting vs:
                $h->calcDays  = 0;
                $h->calcLabel = 'Bekliyor';
            }
        }

        return view('holidays.index', compact('holidays'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'holiday_name' => 'required|string|max:255',
            'description'  => 'nullable|string',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date',
            'status'       => 'required|in:active,waiting',
        ]);

        try {
            Holiday::create($request->all());
        } catch (QueryException $ex) {
            return redirect()->back()
                ->withErrors(['error'=>'Tatil eklenemedi: '.$ex->getMessage()]);
        }

        return redirect()->back()->with('status','Tatil kaydı eklendi!');
    }

    public function update(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($id);

        $request->validate([
            'holiday_name' => 'required|string|max:255',
            'description'  => 'nullable|string',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date',
            'status'       => 'required|in:active,waiting',
        ]);

        try {
            $holiday->update($request->all());
        } catch (QueryException $ex) {
            return redirect()->back()
                ->withErrors(['error'=>'Tatil güncelleme hatası: '.$ex->getMessage()]);
        }

        return redirect()->back()->with('status','Tatil güncellendi!');
    }

    public function destroy($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return redirect()->back()->with('status','Tatil silindi!');
    }
}
