<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Artisan;

class CronController extends Controller
{
    public function run($token)
    {
        if ($token !== env('CRON_SECRET', 'my-secret')) {
            abort(403, 'Unauthorized');
        }
        Artisan::call('schedule:run');
        return "Schedule run executed at: " . now();
    }
}
