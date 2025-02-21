<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkManagerControlsTable extends Migration
{
    public function up()
    {
        Schema::create('work_manager_controls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('pause')->default(false); // true ise WorkManager duracak
            $table->integer('pause_duration')->nullable(); // Durdurma süresi (dakika cinsinden)
            $table->timestamp('resume_at')->nullable();  // Yeniden başlama zamanı (opsiyonel)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('work_manager_controls');
    }
}
