<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parkings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('capacity')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::table('meeting_rooms', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('owner_id')->constrained('locations')->nullOnDelete();
        });

        Schema::create('room_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_room_id')->constrained('meeting_rooms')->cascadeOnDelete();
            $table->string('assignable_type');
            $table->unsignedBigInteger('assignable_id');
            $table->timestamps();

            $table->unique(
                ['meeting_room_id', 'assignable_type', 'assignable_id'],
                'room_assignments_room_assignable_unique'
            );
            $table->index(['assignable_type', 'assignable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_assignments');

        Schema::table('meeting_rooms', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });

        Schema::dropIfExists('parkings');
    }
};
