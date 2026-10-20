<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_id')->constrained('regionals')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('invite_hint')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['regional_id', 'slug']);
        });

        Schema::create('event_whatsapp_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('whatsapp_group_id')->constrained('whatsapp_groups')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'whatsapp_group_id'], 'ewg_event_group_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_whatsapp_group');
        Schema::dropIfExists('whatsapp_groups');
    }
};
