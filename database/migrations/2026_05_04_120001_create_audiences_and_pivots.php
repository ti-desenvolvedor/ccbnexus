<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audiences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('audience_event', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('audience_id')->constrained('audiences')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'audience_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audience_event');
        Schema::dropIfExists('audiences');
    }
};
