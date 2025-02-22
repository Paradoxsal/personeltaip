<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserLocation;
use App\Events\LocationUpdatedEvent;

class LocationController extends Controller
{
    /**
     * Flutter’dan gelen (POST) konum verilerini kaydetmek için store metodu.
     */
    public function store(Request $request)
    {
        // Flutter tarafından gönderilen örnek JSON:
        // {
        //   "user_id": "1",
        //   "latitude": 40.123456,
        //   "longitude": 29.123456,
        //   "timestamp": "2025-01-01T12:00:00"
        // }

        // Validasyon
        $validated = $request->validate([
            'user_id'   => 'required|numeric',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'timestamp' => 'nullable|string', // İstersen date format check de ekleyebilirsin
        ]);

        // Veritabanına kaydet
        $location = UserLocation::create([
            'user_id'   => $validated['user_id'],
            'latitude'  => $validated['latitude'],
            'longitude' => $validated['longitude'],
            // Flutter’dan gelen timestamp boş ise şimdiki zaman
            'timestamp' => $validated['timestamp'] ?? now(),
        ]);

        // Konum kaydı başarılı olduktan sonra event’i yayınlayalım:
        event(new LocationUpdatedEvent(
            $validated['user_id'],
            $validated['latitude'],
            $validated['longitude']
        ));

        return response()->json([
            'status'   => 'success',
            'location' => $location,
        ]);
    }
}
