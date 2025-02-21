<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('halfday_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->enum('type', ['morning', 'afternoon', 'full_day', 'rapor']);
            $table->integer('days_count')->default(1);
            $table->string('rapor_file')->nullable();
            $table->enum('reason', ['sick', 'leave', 'report'])->default('leave');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('halfday_requests');
    }
};
