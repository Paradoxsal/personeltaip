<?php

namespace App\Http\Controllers;

use App\Models\GeoLog;
use Illuminate\Http\Request;
use App\Events\LocationUpdatedEvent; // ✅ Event'ı ekleyin

class GeoLogController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lat'     => 'required|numeric',
            'lng'     => 'required|numeric',
            'user_id' => 'required|integer',
        ]);

        $loc = GeoLog::create([
            'user_id' => $validated['user_id'],
            'lat'     => $validated['lat'],
            'lng'     => $validated['lng'],
        ]);

        // 👇 Event'ı tetikle
        broadcast(new LocationUpdatedEvent(
            $validated['user_id'],
            $validated['lat'],
            $validated['lng']
        ))->toOthers();

        return response()->json([
            'success' => true,
            'data'    => $loc
        ], 200);
    }
}