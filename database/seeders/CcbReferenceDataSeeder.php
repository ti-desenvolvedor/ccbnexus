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
            ['name' => 'Batismo', 'slug' => 'batismo', 'sort_order' => 10, 'bulletin' => true],
            ['name' => 'Santa Ceia', 'slug' => 'santa-ceia', 'sort_order' => 20, 'bulletin' => true],
            ['name' => 'Reunião', 'slug' => 'reuniao', 'sort_order' => 30, 'bulletin' => false],
            ['name' => 'Ensaio Regional', 'slug' => 'ensaio-regional', 'sort_order' => 40, 'bulletin' => true],
            ['name' => 'Reunião da Mocidade', 'slug' => 'reuniao-mocidade', 'sort_order' => 50, 'bulletin' => true],
            ['name' => 'Culto para Jovens (12+)', 'slug' => 'culto-jovens-12', 'sort_order' => 60, 'bulletin' => true],
            ['name' => 'RCO', 'slug' => 'rco', 'sort_order' => 70, 'bulletin' => true],
            ['name' => 'RMA', 'slug' => 'rma', 'sort_order' => 80, 'bulletin' => true],
            ['name' => 'RA', 'slug' => 'ra', 'sort_order' => 90, 'bulletin' => true],
            ['name' => 'RRM', 'slug' => 'rrm', 'sort_order' => 100, 'bulletin' => true],
            ['name' => 'RGA', 'slug' => 'rga', 'sort_order' => 110, 'bulletin' => true],
            ['name' => 'AGO', 'slug' => 'ago', 'sort_order' => 120, 'bulletin' => false],
            ['name' => 'RGE', 'slug' => 'rge', 'sort_order' => 130, 'bulletin' => false],
            ['name' => 'Reunião Organistas', 'slug' => 'reuniao-organistas', 'sort_order' => 140, 'bulletin' => true],
            ['name' => 'Obra da Piedade', 'slug' => 'reuniao-obra-piedade', 'sort_order' => 150, 'bulletin' => true],
            ['name' => 'Comunicado', 'slug' => 'comunicado', 'sort_order' => 160, 'bulletin' => true],
            ['name' => 'Mocidade Online (RMO)', 'slug' => 'mocidade-online', 'sort_order' => 55, 'bulletin' => true],
            ['name' => 'Exame Musical', 'slug' => 'exame-musical', 'sort_order' => 170, 'bulletin' => false],
        ];

        foreach ($eventTypes as $row) {
            EventType::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                    'meta' => [
                        'default_include_on_baptism_list' => (bool) $row['bulletin'],
                        'default_whatsapp_enabled' => ! $row['bulletin'] || in_array($row['slug'], ['batismo', 'reuniao-mocidade', 'ensaio-regional'], true),
                        'report_section' => match ($row['slug']) {
                            'batismo' => 'batismos',
                            'reuniao-mocidade', 'mocidade-online' => 'mocidade',
                            'culto-jovens-12' => 'jovens12',
                            'ensaio-regional' => 'ensaios',
                            'reuniao' => 'reunioes',
                            'reuniao-organistas' => 'organistas',
                            'reuniao-obra-piedade' => 'obra-piedade',
                            'ra' => 'ra',
                            'rrm' => 'rrm',
                            'rga' => 'rga',
                            'rco' => 'rco',
                            'rma' => 'rma',
                            'santa-ceia' => 'santa-ceia',
                            'comunicado' => 'avisos',
                            default => null,
                        },
                    ],
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
