<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_notice_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_id')->nullable()->constrained('regionals')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['regional_id', 'is_active']);
            $table->index(['regional_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_notice_templates');
    }
};

