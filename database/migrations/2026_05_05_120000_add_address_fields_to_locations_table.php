<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('number', 32)->nullable()->after('line1');
            $table->string('complement')->nullable()->after('number');
            $table->string('district')->nullable()->after('complement')->comment('Bairro');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['number', 'complement', 'district']);
        });
    }
};
