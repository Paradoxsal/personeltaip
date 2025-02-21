<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();   // Hangi kullanıcıya ait
            $table->decimal('latitude', 10, 7);               // Enlem
            $table->decimal('longitude', 10, 7);              // Boylam
            $table->timestamp('timestamp')->nullable();       // Konumun alındığı zaman
            $table->timestamps();                             // created_at, updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_locations');
    }
};
