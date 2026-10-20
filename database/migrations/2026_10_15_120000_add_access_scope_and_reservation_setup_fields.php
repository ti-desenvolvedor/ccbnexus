<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_requests', function (Blueprint $table) {
            $table->foreignId('regional_id')->nullable()->after('message')->constrained('regionals')->nullOnDelete();
            $table->foreignId('administration_id')->nullable()->after('regional_id')->constrained('administrations')->nullOnDelete();
            $table->foreignId('provisioned_user_id')->nullable()->after('review_note')->constrained('users')->nullOnDelete();
        });

        Schema::table('room_reservations', function (Blueprint $table) {
            $table->unsignedSmallInteger('setup_minutes')->default(0)->after('ends_at');
            $table->unsignedSmallInteger('teardown_minutes')->default(0)->after('setup_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('access_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('regional_id');
            $table->dropConstrainedForeignId('administration_id');
            $table->dropConstrainedForeignId('provisioned_user_id');
        });

        Schema::table('room_reservations', function (Blueprint $table) {
            $table->dropColumn(['setup_minutes', 'teardown_minutes']);
        });
    }
};
