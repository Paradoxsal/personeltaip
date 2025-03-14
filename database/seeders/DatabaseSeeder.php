<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call([
            UsersTableSeeder::class,
            UnitsTableSeeder::class, // Bu şekilde diğer seed dosyalarını ekle
            LocationsTableSeeder::class,
           // AttendancesTableSeeder::class,
        ]);
    }
}
