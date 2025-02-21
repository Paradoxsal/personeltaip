<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkManagerControl;
use Carbon\Carbon;

class WorkManagerControlSeeder extends Seeder
{
    public function run()
    {
        // Örnek kayıt: user_id 1 için WorkManager durumu aktif ve 60 dakika durdurulmuş olarak ayarlanmış
        WorkManagerControl::create([
            'user_id'       => 1,
            'pause'         => true, // WorkManager durdurulmuş
            'pause_duration'=> 60,   // 60 dakika boyunca duracak
            'resume_at'     => Carbon::now()->addMinutes(60), // 60 dakika sonra yeniden başlayacak
        ]);
    }
}
