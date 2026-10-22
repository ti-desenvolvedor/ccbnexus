<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('baptism_list_report_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('baptism_list_report_id')
                ->constrained('baptism_list_reports', 'id', 'blrc_report_fk')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->unique(['baptism_list_report_id', 'user_id'], 'blrc_report_user_uq');
            $table->index(['baptism_list_report_id', 'checked_at'], 'blrc_report_checked_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baptism_list_report_checks');
    }
};
