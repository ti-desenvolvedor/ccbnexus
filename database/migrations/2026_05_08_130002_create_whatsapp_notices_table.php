<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained('events')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('whatsapp_notice_templates')->cascadeOnDelete();
            $table->foreignId('regional_id')->nullable()->constrained('regionals')->nullOnDelete();
            $table->string('title')->nullable();
            $table->longText('body_final');
            $table->string('status', 16)->default('draft'); // draft | sent_manual
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['regional_id', 'status']);
            $table->index(['event_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_notices');
    }
};

