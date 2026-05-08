<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('dress_code', 32)->nullable()->after('attendance_mode');
            $table->boolean('whatsapp_enabled')->default(false)->after('dress_code');
            // FK é adicionada em migration posterior (para garantir ordem).
            $table->unsignedBigInteger('whatsapp_notice_template_id')->nullable()->after('whatsapp_enabled');
            $table->index('whatsapp_notice_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['whatsapp_notice_template_id']);
            $table->dropColumn(['dress_code', 'whatsapp_enabled', 'whatsapp_notice_template_id']);
        });
    }
};

