<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('include_on_baptism_list')->default(false)->after('whatsapp_enabled');
            $table->boolean('has_libras')->default(false)->after('include_on_baptism_list');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['include_on_baptism_list', 'has_libras']);
        });
    }
};
