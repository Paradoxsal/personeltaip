<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHolidaysTable extends Migration
{
    public function up()
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // eğer "herkese" tatil diyorsanız, bu alan opsiyonel
            $table->string('holiday_name');   // tatil adı
            $table->text('description')->nullable(); // açıklama
            $table->date('start_date');       // tatil başlangıcı
            $table->date('end_date');         // tatil bitişi
            $table->enum('status', ['active','waiting'])->default('waiting'); // tatil başlasın/beklemede
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('holidays');
    }
}
