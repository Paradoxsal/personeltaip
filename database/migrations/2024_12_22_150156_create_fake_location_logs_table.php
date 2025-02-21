<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFakeLocationLogsTable extends Migration
{
    public function up()
    {
        Schema::create('fake_location_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('device_info')->nullable();
            $table->string('fake_location')->nullable(); // "lat,lng" formatında
            $table->timestamp('detected_at')->useCurrent(); // tespit anı
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fake_location_logs');
    }
}
