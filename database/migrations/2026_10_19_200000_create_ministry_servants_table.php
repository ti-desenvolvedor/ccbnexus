<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ministry_servants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_id')->constrained('regionals')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('name_key');
            $table->string('ministry');
            $table->string('phone', 11);
            $table->timestamps();

            $table->unique(['regional_id', 'name_key']);
            $table->unique(['regional_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ministry_servants');
    }
};
