<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id(); // Primary key (id)
            $table->string('unit_name')->nullable(); // Unit name (varchar(255))
            $table->string('unit_head')->nullable(); // Unit head (varchar(255), unique)
            $table->string('unit_location')->nullable(); // Unit location (varchar(255))
            $table->timestamps(); // created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('units');
    }
};
