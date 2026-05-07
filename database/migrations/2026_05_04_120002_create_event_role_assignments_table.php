<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('event_role_template_id')->constrained('event_role_templates')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assignee_label')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'event_role_template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_role_assignments');
    }
};
