<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prayer_houses', function (Blueprint $table) {
            $table->string('official_code', 32)->nullable()->after('administration_id');
            $table->unique('official_code');
            $table->index('official_code');
        });
    }

    public function down(): void
    {
        Schema::table('prayer_houses', function (Blueprint $table) {
            $table->dropUnique(['official_code']);
            $table->dropColumn('official_code');
        });
    }
};
