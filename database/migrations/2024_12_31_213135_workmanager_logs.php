<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workmanager_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->boolean('sendMorningGunaydin')->default(false);
            $table->boolean('checkGiris09')->default(false);
            $table->boolean('checkGiris11')->default(false);
            $table->boolean('checkGiris12_20')->default(false);
            $table->boolean('checkCikis1655')->default(false);
            $table->boolean('checkCikis1715')->default(false);
            $table->boolean('checkCikisAfter1720')->default(false);
            $table->boolean('checkNoRecords2130')->default(false);
            $table->enum('workmanager_start', ['yes','no'])->default('no');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workmanager_logs');
    }
};
