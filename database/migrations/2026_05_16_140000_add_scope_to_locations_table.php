<?php

use App\Models\Administration;
use App\Models\Location;
use App\Models\PrayerHouse;
use App\Models\Regional;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('administration_id')->nullable()->after('id')->constrained('administrations')->nullOnDelete();
            $table->foreignId('regional_id')->nullable()->after('administration_id')->constrained('regionals')->nullOnDelete();
            $table->index('administration_id');
            $table->index('regional_id');
        });

        foreach (PrayerHouse::query()->whereNotNull('location_id')->with('administration')->get() as $house) {
            if ($house->location_id && $house->administration_id) {
                Location::query()->where('id', $house->location_id)->update([
                    'administration_id' => $house->administration_id,
                ]);
            }
        }

        foreach (Administration::query()->whereNotNull('location_id')->get() as $admin) {
            Location::query()->where('id', $admin->location_id)->update([
                'administration_id' => $admin->id,
            ]);
        }

        foreach (Regional::query()->whereNotNull('location_id')->get() as $regional) {
            Location::query()->where('id', $regional->location_id)->update([
                'regional_id' => $regional->id,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign(['administration_id']);
            $table->dropForeign(['regional_id']);
            $table->dropColumn(['administration_id', 'regional_id']);
        });
    }
};
