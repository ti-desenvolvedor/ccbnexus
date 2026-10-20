<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_departments', function (Blueprint $table) {
            $table->foreignId('public_subgroup_id')
                ->nullable()
                ->after('prayer_house_id')
                ->constrained('public_subgroups')
                ->nullOnDelete();
        });

        Schema::table('public_departments', function (Blueprint $table) {
            $table->unique(['scope', 'regional_id', 'slug'], 'public_departments_regional_slug_unique');
            $table->unique(['scope', 'administration_id', 'slug'], 'public_departments_administration_slug_unique');
            $table->unique(['scope', 'prayer_house_id', 'slug'], 'public_departments_prayer_house_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('public_departments', function (Blueprint $table) {
            $table->dropUnique('public_departments_regional_slug_unique');
            $table->dropUnique('public_departments_administration_slug_unique');
            $table->dropUnique('public_departments_prayer_house_slug_unique');
            $table->dropConstrainedForeignId('public_subgroup_id');
        });
    }
};
