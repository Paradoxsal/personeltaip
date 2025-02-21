<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id(); // id sütunu (bigint(20) UNSIGNED)
            $table->string('location_name')->nullable(); // Lokasyon adı (varchar(255))
            $table->string('location_address')->nullable(); // Lokasyon konumu (varchar(255))
            $table->string('created_by')->nullable(); // Lokasyonu kaydeden kişi (user_id)
            $table->timestamps(); // created_at ve updated_at sütunları (timestamp, nullable)

            // created_by sütununun users tablosundaki id sütunu ile ilişkilendirilmesi
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('locations');
    }
}