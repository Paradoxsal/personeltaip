<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManualNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('manual_notifications', function (Blueprint $table) {
            $table->id();
            
            // Basic fields
            $table->string('title');
            $table->text('body')->nullable();
            
            // For scheduling
            $table->timestamp('scheduled_at')->nullable();
            
            // Who will receive? Could be "all", or a specific user, or a certain user group, etc.
            $table->string('target_type')->default('all');
            
            // Current status of this notification: "pending", "sent", "failed", etc.
            $table->string('status')->default('pending');

            $table->string('user_id')->nullable();
            
            // Just for reference: when it was actually sent
            $table->timestamp('sent_at')->nullable();

            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('manual_notifications');
    }
}
