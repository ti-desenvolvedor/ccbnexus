<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7);
            $table->string('status', 16);
            $table->string('source', 32);
            $table->json('years');
            $table->unsignedInteger('inserted')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->text('message')->nullable();
            $table->timestamp('ran_at');
            $table->timestamps();

            $table->index(['period', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_sync_logs');
    }
};
