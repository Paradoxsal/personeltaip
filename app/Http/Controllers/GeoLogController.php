<?php

namespace App\Http\Controllers;

use App\Models\GeoLog;
use Illuminate\Http\Request;

class GeoLogController extends Controller
{
    public function store(Request $request)
    {
        // "lat", "lng", "user_id" alalım
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

        return response()->json([
            'success' => true,
            'data'    => $loc
        ], 200);
    }
}
