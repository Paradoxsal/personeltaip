<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // bigint(20) UNSIGNED auto increment
            $table->string('name')->unique(); // varchar(255), unique
            $table->string('email')->nullable(); // varchar(255), nullable
            $table->string('password'); // varchar(255)
            $table->string('phone')->nullable(); // varchar(255), nullable

            $table->string('check_in_location')->nullable();  // varchar(255), nullable
            $table->string('check_out_location')->nullable(); // varchar(255), nullable

            $table->string('device_info')->nullable(); // varchar(255), nullable
            $table->integer('cihaz_yetki')->nullable()->default('0');; // int(11), nullable
            $table->integer('units_id')->nullable();    // int(11), nullable
            $table->integer('role')->nullable();        // int(11), nullable

            // EKLEDİĞİMİZ SÜTUN: fcm_role => "yes/no" veya "null", default "no"
            $table->string('fcm_role')->nullable()->default('no');
            $table->integer('banned')->nullable()->default('0');
            $table->string('banned_log')->nullable(); // varchar(255), nullable
            $table->integer('device_info_flag')->nullable()->default('0');

            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            $table->rememberToken();   // varchar(100), nullable
            $table->timestamps();      // created_at ve updated_at, timestamp, nullable
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
