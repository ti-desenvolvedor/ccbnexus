<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('attendance_mode', 32)->default('in_person')->after('meeting_room_id');
            $table->unsignedInteger('expected_attendees')->nullable()->after('attendance_mode');

            $table->boolean('needs_sound_controller')->default(false)->after('expected_attendees');
            $table->boolean('needs_av')->default(false)->after('needs_sound_controller');
            $table->boolean('needs_parking')->default(false)->after('needs_av');
            $table->boolean('needs_meals')->default(false)->after('needs_parking');
            $table->boolean('meal_coffee')->default(false)->after('needs_meals');
            $table->boolean('meal_lunch')->default(false)->after('meal_coffee');
            $table->boolean('meal_snack')->default(false)->after('meal_lunch');
            $table->boolean('meal_dinner')->default(false)->after('meal_snack');
            $table->boolean('needs_nursing')->default(false)->after('meal_dinner');
            $table->boolean('needs_valet')->default(false)->after('needs_nursing');
            $table->boolean('needs_other_materials')->default(false)->after('needs_valet');
            $table->text('other_materials_note')->nullable()->after('needs_other_materials');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'attendance_mode',
                'expected_attendees',
                'needs_sound_controller',
                'needs_av',
                'needs_parking',
                'needs_meals',
                'meal_coffee',
                'meal_lunch',
                'meal_snack',
                'meal_dinner',
                'needs_nursing',
                'needs_valet',
                'needs_other_materials',
                'other_materials_note',
            ]);
        });
    }
};
