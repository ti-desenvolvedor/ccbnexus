<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recuperação se a migration falhou a meio (índice longo no MySQL).
        Schema::dropIfExists('baptism_list_report_items');
        Schema::dropIfExists('baptism_list_reports');

        Schema::create('baptism_list_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_id')->constrained('regionals')->cascadeOnDelete();
            $table->date('issue_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedSmallInteger('revision')->default(0);
            $table->timestamp('edition_at')->nullable();
            $table->string('status', 32)->default('draft');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['regional_id', 'issue_date', 'revision'], 'blr_unique_rev');
            $table->index(['regional_id', 'issue_date'], 'blr_regional_issue_idx');
        });

        Schema::create('baptism_list_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('baptism_list_report_id')
                ->constrained('baptism_list_reports', 'id', 'blri_report_fk')
                ->cascadeOnDelete();
            $table->string('section_key', 64);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['baptism_list_report_id', 'section_key'], 'blri_report_section_idx');
            $table->index(['source_type', 'source_id'], 'blri_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baptism_list_report_items');
        Schema::dropIfExists('baptism_list_reports');
    }
};
