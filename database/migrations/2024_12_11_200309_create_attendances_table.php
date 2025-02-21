<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id(); // id sütunu (bigint(20) UNSIGNED)
            $table->unsignedBigInteger('user_id'); // user_id sütunu (bigint(20) UNSIGNED)
            $table->string('user_name')->nullable();
            $table->timestamp('check_in_time')->nullable(); // check_in_time sütunu (timestamp, nullable)
            $table->string('check_in_location')->nullable(); // check_in_location sütunu (varchar(255), nullable)
            $table->timestamp('check_out_time')->nullable(); // check_out_time sütunu (timestamp, nullable)
            $table->string('check_out_location')->nullable(); // check_out_location sütunu (varchar(255), nullable)
            $table->timestamps(); // created_at ve updated_at sütunları (timestamp, nullable)

            // user_id sütununun users tablosundaki id sütunu ile ilişkilendirilmesi
           // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}