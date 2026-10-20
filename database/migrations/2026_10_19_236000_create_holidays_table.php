<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('observed_on');
            $table->string('name');
            $table->string('scope', 16);
            $table->string('state', 2)->nullable();
            $table->string('source_key')->unique();
            $table->timestamps();

            $table->index(['observed_on', 'scope', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
