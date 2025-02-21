<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LocationsTableSeeder extends Seeder
{
    public function run()
    {
        $locations = [
            [
                'location_name' => 'Office A',
                'location_address' => '123 Main Street, New York',
                'created_by' => 'osmanzaman', // John Doe
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'location_name' => 'Factory B',
                'location_address' => '456 Industrial Road, Los Angeles',
                'created_by' => 'mustafakemak', // Jane Smith
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Diğer lokasyonları buraya ekleyin...
        ];

        DB::table('locations')->insert($locations);
    }
}
