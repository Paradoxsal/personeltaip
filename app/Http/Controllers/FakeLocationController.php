<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FakeLocationLog;
use App\Models\User; // eğer user bilgisi çekecekseniz

class FakeLocationController extends Controller
{
    public function reportFakeLocation(Request $request)
    {
        // 1) Validate
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'user_name' => 'nullable|string',
            'device_info' => 'nullable|string',
            'fake_lat' => 'required|numeric',
            'fake_lng' => 'required|numeric',
        ]);

        // 2) Kaydet
        $fakeLoc = FakeLocationLog::create([
            'user_id' => $validated['user_id'],
            'user_name' => $validated['user_name'] ?? null,
            'device_info' => $validated['device_info'] ?? null,
            'fake_location' => $validated['fake_lat'].','.$validated['fake_lng'],
            // 'real_location' => '...' eğer bulabiliyorsanız
            'detected_at' => now(),
        ]);

        return response()->json([
            'message' => 'Sahte konum logu eklendi',
            'data' => $fakeLoc
        ], 201);
    }
}
