<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 40)->nullable()->after('password');
            $table->string('google_id')->nullable()->unique()->after('phone');
            $table->foreignId('regional_id')->nullable()->after('google_id')->constrained('regionals')->nullOnDelete();
            $table->foreignId('administration_id')->nullable()->after('regional_id')->constrained('administrations')->nullOnDelete();
            $table->foreignId('prayer_house_id')->nullable()->after('administration_id')->constrained('prayer_houses')->nullOnDelete();
            $table->boolean('is_super_admin')->default(false)->after('prayer_house_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['regional_id']);
            $table->dropForeign(['administration_id']);
            $table->dropForeign(['prayer_house_id']);
            $table->dropColumn([
                'phone',
                'google_id',
                'regional_id',
                'administration_id',
                'prayer_house_id',
                'is_super_admin',
            ]);
        });
    }
};
