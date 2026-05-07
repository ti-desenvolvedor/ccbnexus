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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->morphs('addressable'); // Administration|PrayerHouse (expand later)
            $table->string('label')->nullable();

            $table->string('postal_code', 32)->nullable();
            $table->string('country', 2)->default('BR');
            $table->string('state', 8)->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('street_line1');
            $table->string('street_line2')->nullable();
            $table->string('number', 32)->nullable();
            $table->string('complement')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
