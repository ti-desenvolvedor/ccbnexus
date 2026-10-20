<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('list_per_page')->default(20)->after('is_super_admin');
            $table->string('ui_theme', 16)->nullable()->after('list_per_page');
            $table->string('ui_palette', 32)->nullable()->after('ui_theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['list_per_page', 'ui_theme', 'ui_palette']);
        });
    }
};
