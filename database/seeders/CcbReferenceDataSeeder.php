<?php

namespace Database\Seeders;

use App\Models\Audience;
use App\Models\EventRoleTemplate;
use App\Models\EventType;
use App\Models\NotificationRuleTemplate;
use App\Models\WhatsAppNoticeTemplate;
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

        $waTemplates = [
            [
                'name' => 'WhatsApp: Reunião local (padrão)',
                'slug' => 'wa-reuniao-local-padrao',
                'is_default' => true,
                'body' => implode("\n", [
                    'ℭ𝔬𝔫𝔤𝔯𝔢𝔤𝔞ç𝔞𝔬 ℭ𝔯𝔦𝔰𝔱𝔞 𝔫𝔬 𝔅𝔯𝔞𝔰𝔦𝔩',
                    '-------------------------------------',
                    'A Paz de Deus! Amém!',
                    '',
                    '📝 C O M U N I C A D O !',
                    '',
                    '{event_title}',
                    '',
                    'Próxima*{weekday}*, dia,',
                    '⏰ {date}, às {time}',
                    '🎯 Local: {location}',
                    '',
                    '{audience_text}',
                    '',
                    '👔 Traje: {dress_code}',
                    '',
                    '{notes}',
                    '',
                    '{signature}',
                ]),
            ],
            [
                'name' => 'WhatsApp: Híbrido (presencial + online)',
                'slug' => 'wa-hibrido',
                'is_default' => false,
                'body' => implode("\n", [
                    '⛔ A Paz de Deus.',
                    '',
                    'Segue informe de {event_title}.',
                    '',
                    '📅 Data: {date}',
                    '⏰ Hora: {time}',
                    '',
                    '📌 Atentar para o local de realização:',
                    '{location_hybrid}',
                    '',
                    '👔 Traje: {dress_code}',
                    '',
                    '{notes}',
                    '',
                    '{signature}',
                ]),
            ],
            [
                'name' => 'WhatsApp: Lembrete com link',
                'slug' => 'wa-lembrete-link',
                'is_default' => false,
                'body' => implode("\n", [
                    '⛔ {event_title}',
                    '',
                    'Prezados irmãos(ãs), A Paz de Deus!',
                    '',
                    'LEMBRETE',
                    '{notes}',
                    '',
                    'Link: {link}',
                    '',
                    '{signature}',
                ]),
            ],
            [
                'name' => 'WhatsApp: Treinamento / Convite (link confirmação)',
                'slug' => 'wa-treinamento-convite',
                'is_default' => false,
                'body' => implode("\n", [
                    '{event_title}',
                    '',
                    '{notes}',
                    '',
                    '📆 Data: {date}',
                    '🕘 Horário: {time}',
                    '📍 Local: {location}',
                    '👕 Traje: {dress_code}',
                    '',
                    '🔗 Confirme sua participação pelo link:',
                    '{link}',
                    '',
                    '{signature}',
                ]),
            ],
        ];

        foreach ($waTemplates as $row) {
            WhatsAppNoticeTemplate::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'body' => $row['body'],
                    'is_active' => true,
                    'is_default' => (bool) ($row['is_default'] ?? false),
                ],
            );
        }
    }
}
