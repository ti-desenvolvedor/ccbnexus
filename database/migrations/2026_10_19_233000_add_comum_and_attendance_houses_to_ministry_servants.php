<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ministry_servants', 'comum_prayer_house_id')) {
            Schema::table('ministry_servants', function (Blueprint $table) {
                $table->foreignId('comum_prayer_house_id')
                    ->nullable()
                    ->after('phone')
                    ->constrained('prayer_houses')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('ministry_servant_prayer_house')) {
            Schema::create('ministry_servant_prayer_house', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ministry_servant_id')->constrained('ministry_servants')->cascadeOnDelete();
                $table->foreignId('prayer_house_id')->constrained('prayer_houses')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['ministry_servant_id', 'prayer_house_id'], 'ministry_servant_house_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ministry_servant_prayer_house');

        if (Schema::hasColumn('ministry_servants', 'comum_prayer_house_id')) {
            Schema::table('ministry_servants', function (Blueprint $table) {
                $table->dropConstrainedForeignId('comum_prayer_house_id');
            });
        }
    }
};
