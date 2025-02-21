<?php

// database/migrations/2023_01_01_000000_create_geo_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeoLogsTable extends Migration
{
    public function up()
    {
        Schema::create('geo_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');  // users tablosuna foreign key olabilir
            $table->decimal('lat', 10, 6);
            $table->decimal('lng', 10, 6);
            $table->string('status');  // "yaklasti" veya "vardi" gibi
            $table->string('notification_go'); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('geo_logs');
    }
}
