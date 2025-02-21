<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_user_devices_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserDevicesTable extends Migration
{
    public function up()
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_info')->unique();
            $table->integer('cihaz_yetki')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_devices');
    }
}
