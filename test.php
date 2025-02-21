<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Artisan;

class CronController extends Controller
{
    public function run($token)
    {
        // Pseudo-code
        $file = '/home/.../public_html/credentials/service_account.json';
        if (!is_readable($file)) {
            echo "FAIL - File not readable or not found.";
        } else {
            // OK => read file, or do something
        }

        // Sonra artisan komutu
        Artisan::call('schedule:run');
        echo "Schedule run executed at: " . now();

    }
}
