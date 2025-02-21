<?php

namespace App\Http\Controllers;

use App\Models\Units;
use Illuminate\Http\Request;

class BirimController extends Controller
{
    // Index: Tüm birimleri listele
    public function index()
    {
        // units tablosundaki tüm kayıtları çek
        $departmanlar = Units::all();

        // Blade'e gönder
        return view('laravel-examples.birimsettiginis', compact('departmanlar'));
    }

    // create() -> Eğer ayrı bir sayfada form açacaksanız kullanabilirsiniz (Modal kullandığımız için gerek olmayabilir)
    public function create()
    {
        return view('birimsettiginis.create');
    }

    // Store: Yeni bir birim kaydet
    public function store(Request $request)
    {
        // Validasyon
        $validated = $request->validate([
            'unit_name'     => 'required|string|max:255',
            'unit_head'     => 'required|string|max:255',
            'unit_location' => 'required|string|max:255',
        ]);

        try {
            Units::create([
                'unit_name'     => $request->unit_name,
                'unit_head'     => $request->unit_head,
                'unit_location' => $request->unit_location,
            ]);

            session()->flash('success', 'Birim başarıyla eklendi!');
        } catch (\Exception $e) {
            session()->flash('error', 'Bir hata oluştu: ' . $e->getMessage());
        }

        return redirect()->route('birimsettiginis.index');
    }

    // Edit: Tek bir birimi düzenleme formu (ayrı bir sayfa kullanırsanız)
    public function edit($id)
    {
        $departman = Units::findOrFail($id);
        return view('birimsettiginis.edit', compact('departman'));
    }

    // Update: Birimi güncelle
    public function update(Request $request, $id)
    {
        $request->validate([
            'unit_name'     => 'required|string|max:255',
            'unit_head'     => 'required|string|max:255',
            'unit_location' => 'required|string|max:255',
        ]);

        $departman = Units::findOrFail($id);

        $departman->update([
            'unit_name'     => $request->unit_name,
            'unit_head'     => $request->unit_head,
            'unit_location' => $request->unit_location,
        ]);

        return redirect()->route('birimsettiginis.index')
            ->with('success', 'Birim başarıyla güncellendi.');
    }

    // Destroy: Birimi sil
    public function destroy($id)
    {
        $departman = Units::findOrFail($id);
        $departman->delete();

        return redirect()->route('birimsettiginis.index')
            ->with('success', 'Birim başarıyla silindi.');
    }
}
