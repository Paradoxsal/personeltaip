<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name'); // Hangi tabloda değişiklik yapıldı
            $table->string('action'); // İşlem türü (create, update, delete)
            $table->text('old_data')->nullable(); // Eski veri (JSON formatında)
            $table->text('new_data')->nullable(); // Yeni veri (JSON formatında)
            $table->unsignedBigInteger('performed_by')->nullable(); // Kim yaptı
            $table->unsignedBigInteger('performed_on')->nullable(); // Kime yapıldı
            $table->timestamps(); // İşlem zamanı
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
