<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_id')->constrained('regionals')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['regional_id', 'slug']);
        });

        Schema::create('public_subgroups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_group_id')->constrained('public_groups')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['public_group_id', 'slug']);
        });

        Schema::create('public_departments', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 32); // regional | administration | prayer_house
            $table->foreignId('regional_id')->nullable()->constrained('regionals')->nullOnDelete();
            $table->foreignId('administration_id')->nullable()->constrained('administrations')->nullOnDelete();
            $table->foreignId('prayer_house_id')->nullable()->constrained('prayer_houses')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['scope', 'regional_id']);
            $table->index(['scope', 'administration_id']);
            $table->index(['scope', 'prayer_house_id']);
        });

        Schema::create('public_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_department_id')->constrained('public_departments')->cascadeOnDelete();
            $table->foreignId('public_subgroup_id')->nullable()->constrained('public_subgroups')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['public_department_id', 'slug']);
        });

        Schema::create('event_public_position', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('public_position_id')->constrained('public_positions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'public_position_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_public_position');
        Schema::dropIfExists('public_positions');
        Schema::dropIfExists('public_departments');
        Schema::dropIfExists('public_subgroups');
        Schema::dropIfExists('public_groups');
    }
};
