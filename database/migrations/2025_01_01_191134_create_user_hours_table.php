<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserHoursTable extends Migration
{
    public function up()
    {
        Schema::create('user_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            // time tipinde (MySQL: 'HH:MM:SS')
            $table->time('morning_start_time')->nullable();
            $table->time('morning_end_time')->nullable();
            $table->time('evening_start_time')->nullable();
            $table->time('evening_end_time')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_hours');
    }
}
