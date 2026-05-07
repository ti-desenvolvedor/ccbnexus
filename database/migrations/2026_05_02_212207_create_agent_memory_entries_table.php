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
        Schema::create('agent_memory_entries', function (Blueprint $table) {
            $table->id();

            $table->string('phase')->index();
            $table->string('stage')->nullable()->index();
            $table->string('title');
            $table->text('body')->nullable();

            $table->string('actor')->default('system'); // human|ci|cursor-agent|system
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->json('tags')->nullable();
            $table->json('context')->nullable();

            $table->timestamps();

            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_memory_entries');
    }
};
