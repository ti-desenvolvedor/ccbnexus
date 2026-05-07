<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('participation', 32)->default('not_answered');
            $table->string('presence_mode', 32)->nullable();
            $table->boolean('meal_coffee')->default(false);
            $table->boolean('meal_lunch')->default(false);
            $table->boolean('meal_snack')->default(false);
            $table->boolean('meal_dinner')->default(false);
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_rsvps');
    }
};
