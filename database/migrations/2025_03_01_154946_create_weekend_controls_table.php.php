<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWeekendControlsTable extends Migration
{
    public function up()
    {
        Schema::create('weekend_controls', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Eğer belirli kullanıcı içinse user_id; "tüm kullanıcılar" için NULL olacak.
            $table->unsignedBigInteger('user_id')->nullable();
            // "Tüm Kullanıcılar" için true, belirli kullanıcı için false
            $table->boolean('all_users')->default(false);
            // Haftanın başlangıç tarihi (örn. o haftanın Pazartesi tarihi)
            $table->date('week_start_date');
            // Bu hafta sonu aktif mi? (true = aktif, false = pasif)
            $table->boolean('weekend_active')->default(false);
            $table->timestamps();

            // Aynı hafta için aynı ayar yalnızca ya global (all_users) ya da kullanıcıya özel olabilir.
            $table->unique(['user_id', 'all_users', 'week_start_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('weekend_controls');
    }
}
