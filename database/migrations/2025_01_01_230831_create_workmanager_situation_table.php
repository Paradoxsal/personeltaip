<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkmanagerSituationTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('workmanager_situation', function (Blueprint $table) {
            $table->id();

            // optional: associate with users
            $table->unsignedBigInteger('user_id')->nullable();

            // e.g. "06:00-09:00" or "06-09,16-18"
            $table->string('active_hours')->nullable();

            // indicates if the workmanager is currently active
            $table->boolean('is_active')->default(false);

            // can store location data (JSON or text)
            $table->text('location_info')->nullable();

            $table->timestamps();

            // optional foreign key
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('workmanager_situation');
    }
}
