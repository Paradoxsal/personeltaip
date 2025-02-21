<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'name' => 'osman zaman',
                'email' => 'osman.zaman2019@hotmail.com',
                'password' => Hash::make('123456'),
                'phone' => '05062996463',
                'check_in_location' => 'Office A',
                'check_out_location' => 'Office A',
                'units_id' => '1',
                'device_info' => '',
                'cihaz_yetki' => 0,
                'role' => 0,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mustafa Kemal Karataş',
                'email' => 'mustafakemalkaratas@gmail.com',
                'password' => Hash::make('123456'),
                'phone' => '05062996463',
                'check_in_location' => 'Factory B',
                'check_out_location' => 'Factory B',
                'units_id' => '1',
                'device_info' => '',
                'cihaz_yetki' => 0,
                'super' => 1,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Diğer kullanıcıları buraya ekleyin...
        ];

        DB::table('users')->insert($users);
    }
}
