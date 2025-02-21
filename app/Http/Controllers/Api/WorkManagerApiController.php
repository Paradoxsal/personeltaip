<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkManagerControl;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WorkManagerApiController extends Controller
{
    public function getCommand(Request $request)
    {
        $userId = $request->input('user_id');
        $command = WorkManagerControl::firstOrCreate(
            ['user_id' => $userId],
            ['pause' => false, 'pause_duration' => 60, 'resume_at' => null]
        );

        // Süre dolmuşsa otomatik resume
        if ($command->pause && Carbon::now()->gt($command->resume_at)) {
            $command->update(['pause' => false, 'resume_at' => null]);
        }

        return response()->json([
            'pause' => $command->pause,
            'pause_duration' => $command->pause_duration,
            'resume_at' => $command->resume_at,
        ]);

        
    }

    public function updateCommand(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'pause' => 'required|boolean',
            'pause_duration' => 'nullable|integer|min:1',
        ]);

        $resumeAt = $request->pause 
            ? Carbon::now()->addMinutes($request->pause_duration ?? 60)
            : null;

        $command = WorkManagerControl::updateOrCreate(
            ['user_id' => $request->user_id],
            [
                'pause' => $request->pause,
                'pause_duration' => $request->pause_duration ?? 60,
                'resume_at' => $resumeAt,
            ]
        );

        return response()->json($command);
    }
}