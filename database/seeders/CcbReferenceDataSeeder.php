<?php

namespace Database\Seeders;

use App\Models\Audience;
use App\Models\EventRoleTemplate;
use App\Models\EventType;
use App\Models\NotificationRuleTemplate;
use Illuminate\Database\Seeder;

class CcbReferenceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $eventTypes = [
            ['name' => 'Batismo', 'slug' => 'batismo', 'sort_order' => 10],
            ['name' => 'Santa Ceia', 'slug' => 'santa-ceia', 'sort_order' => 20],
            ['name' => 'Reunião', 'slug' => 'reuniao', 'sort_order' => 30],
            ['name' => 'Ensaio Regional', 'slug' => 'ensaio-regional', 'sort_order' => 40],
        ];

        foreach ($eventTypes as $row) {
            EventType::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                ],
            );
        }

        $eventRoles = [
            ['name' => 'Preside', 'slug' => 'preside', 'sort_order' => 10],
            ['name' => 'Atende', 'slug' => 'atende', 'sort_order' => 20],
            ['name' => 'Encarregado', 'slug' => 'encarregado', 'sort_order' => 30],
        ];

        foreach ($eventRoles as $row) {
            EventRoleTemplate::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                ],
            );
        }

        $notificationRules = [
            ['name' => 'Aviso: 30 dias antes', 'days_before' => 30, 'sort_order' => 10],
            ['name' => 'Aviso: 15 dias antes', 'days_before' => 15, 'sort_order' => 20],
            ['name' => 'Aviso: 7 dias antes', 'days_before' => 7, 'sort_order' => 30],
            ['name' => 'Aviso: 1 dia antes', 'days_before' => 1, 'sort_order' => 40],
        ];

        foreach ($notificationRules as $row) {
            NotificationRuleTemplate::query()->updateOrCreate(
                ['days_before' => $row['days_before']],
                [
                    'name' => $row['name'],
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                ],
            );
        }

        $audiences = [
            ['name' => 'Membros', 'slug' => 'membros', 'sort_order' => 10],
            ['name' => 'Visitantes', 'slug' => 'visitantes', 'sort_order' => 20],
            ['name' => 'Jovens', 'slug' => 'jovens', 'sort_order' => 30],
        ];

        foreach ($audiences as $row) {
            Audience::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                ],
            );
        }
    }
}
