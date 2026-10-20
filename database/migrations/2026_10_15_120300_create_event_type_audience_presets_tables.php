<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_type_audience_presets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_type_id')->unique()->constrained('event_types')->cascadeOnDelete();
            $table->text('invitation_paragraph')->nullable();
            $table->string('report_layout', 32)->default('list_only');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('event_type_audience_preset_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_type_audience_preset_id')
                ->constrained('event_type_audience_presets', 'id', 'etap_preset_fk')
                ->cascadeOnDelete();
            $table->string('position_slug', 80);
            $table->timestamps();

            $table->unique(
                ['event_type_audience_preset_id', 'position_slug'],
                'etap_preset_slug_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_type_audience_preset_positions');
        Schema::dropIfExists('event_type_audience_presets');
    }
};
