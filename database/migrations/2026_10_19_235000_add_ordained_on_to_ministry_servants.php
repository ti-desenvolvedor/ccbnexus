<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ministry_servants', 'ordained_on')) {
            return;
        }

        $afterComum = Schema::hasColumn('ministry_servants', 'comum_prayer_house_id');

        Schema::table('ministry_servants', function (Blueprint $table) use ($afterComum) {
            $column = $table->date('ordained_on')->nullable();
            if ($afterComum) {
                $column->after('comum_prayer_house_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ministry_servants', 'ordained_on')) {
            return;
        }

        Schema::table('ministry_servants', function (Blueprint $table) {
            $table->dropColumn('ordained_on');
        });
    }
};
