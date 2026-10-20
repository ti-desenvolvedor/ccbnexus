<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ministry_servants', function (Blueprint $table) {
            $table->foreignId('public_position_id')
                ->nullable()
                ->after('ministry')
                ->constrained('public_positions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ministry_servants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('public_position_id');
        });
    }
};
