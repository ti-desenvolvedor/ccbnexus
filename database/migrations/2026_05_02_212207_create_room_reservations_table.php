<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('room_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_room_id')->constrained('meeting_rooms')->cascadeOnDelete();

            $table->string('title');
            $table->text('notes')->nullable();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->string('status', 32)->default('pending'); // draft|pending|approved|rejected|cancelled

            $table->boolean('requires_approval')->default(false);

            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('moderator_note')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['meeting_room_id', 'starts_at', 'ends_at']);
            $table->index(['status', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_reservations');
    }
};
