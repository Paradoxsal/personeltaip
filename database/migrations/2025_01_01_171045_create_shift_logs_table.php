<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftLogsTable extends Migration
{
    public function up()
    {
        Schema::create('shift_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('shift_date');          // Mesaiye kalınan gün
            $table->boolean('is_on_shift')->default(true); 
                // "mesaiye kalacak" => true, "kalmadı" => false
            $table->string('no_shift_reason')->nullable(); 
                // "mesaiye kalmadı"ysa sebebi
            $table->dateTime('exit_time')->nullable(); 
                // Kullanıcı çıkış yaptığında set edilecek, yoksa null
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_logs');
    }
}
