<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_notification_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->unsignedInteger('days_before');
            $table->timestamp('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->string('channel', 32)->default('log');
            $table->text('payload')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'days_before', 'scheduled_for'], 'event_notif_unique_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_notification_dispatches');
    }
};
