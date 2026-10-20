<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regional_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_id')->constrained('regionals')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('category', 32)->default('geral');
            $table->date('valid_from');
            $table->date('valid_until');
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['regional_id', 'valid_from', 'valid_until'], 'rn_regional_valid_idx');
        });

        Schema::create('regional_schedule_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_id')->constrained('regionals')->cascadeOnDelete();
            $table->string('change_type', 32);
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->date('valid_from');
            $table->date('valid_until');
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['regional_id', 'valid_from', 'valid_until'], 'rsc_regional_valid_idx');
        });

        Schema::create('regional_special_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_id')->constrained('regionals')->cascadeOnDelete();
            $table->string('mode', 32);
            $table->string('title');
            $table->text('body');
            $table->date('collection_on')->nullable();
            $table->date('collection_until')->nullable();
            $table->date('valid_from');
            $table->date('valid_until');
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['regional_id', 'valid_from', 'valid_until'], 'rspc_regional_valid_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regional_special_collections');
        Schema::dropIfExists('regional_schedule_changes');
        Schema::dropIfExists('regional_notices');
    }
};
