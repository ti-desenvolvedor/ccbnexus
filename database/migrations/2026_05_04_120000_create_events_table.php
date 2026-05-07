<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_id')->nullable()->constrained('regionals')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('event_type_id')->nullable()->constrained('event_types')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('meeting_room_id')->nullable()->constrained('meeting_rooms')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('parent_event_id')->nullable();
            $table->boolean('is_occurrence')->default(false);
            $table->string('recurrence_frequency', 32)->nullable();
            $table->date('recurrence_until')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['regional_id', 'starts_at']);
            $table->index(['parent_event_id', 'is_occurrence']);
            $table->index('status');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreign('parent_event_id')->references('id')->on('events')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['parent_event_id']);
        });
        Schema::dropIfExists('events');
    }
};
