<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            // "entry_exit" veya "new_version"
            $table->string('setting_type');

            // entry_exit verileri:
            $table->time('morning_start_time')->nullable();
            $table->time('morning_end_time')->nullable();
            $table->time('evening_start_time')->nullable();
            $table->time('evening_end_time')->nullable();

            // new_version verileri:
            $table->string('version_link')->nullable();
            $table->text('version_desc')->nullable();
            // "send" (gönder) veya "wait" (beklet) vs.
            $table->string('version_status')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
}
