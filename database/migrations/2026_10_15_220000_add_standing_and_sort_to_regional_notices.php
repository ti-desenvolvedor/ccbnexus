<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regional_notices', function (Blueprint $table) {
            $table->boolean('is_standing')->default(false)->after('is_active');
            $table->unsignedSmallInteger('sort_order')->default(100)->after('is_standing');
        });
    }

    public function down(): void
    {
        Schema::table('regional_notices', function (Blueprint $table) {
            $table->dropColumn(['is_standing', 'sort_order']);
        });
    }
};
