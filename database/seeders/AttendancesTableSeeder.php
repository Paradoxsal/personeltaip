<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendancesTableSeeder extends Seeder
{
    public function run()
    {
        $attendances = [
            [
                'user_id' => 1, // John Doe
                'check_in_time' => now()->subHours(2),
                'check_in_location' => 'Office A',
                'check_out_time' => now()->subMinutes(30),
                'check_out_location' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 2, // Jane Smith
                'check_in_time' => now()->subHours(3),
                'check_in_location' => 'Factory B',
                'check_out_time' => now()->subMinutes(30),
                'check_out_location' => 'Factory B',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('attendances')->insert($attendances);
    }
}
