<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regionals', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('id')->constrained('locations')->nullOnDelete();
        });

        Schema::table('administrations', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('regional_id')->constrained('locations')->nullOnDelete();
        });

        Schema::table('prayer_houses', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('administration_id')->constrained('locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prayer_houses', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });

        Schema::table('administrations', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });

        Schema::table('regionals', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });
    }
};
