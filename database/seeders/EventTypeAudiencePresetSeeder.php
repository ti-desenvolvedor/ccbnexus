<?php

namespace Database\Seeders;

use App\Models\EventType;
use App\Models\EventTypeAudiencePreset;
use Illuminate\Database\Seeder;

class EventTypeAudiencePresetSeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            'batismo' => [
                'paragraph' => 'Com alegria convidamos os irmãos para o batismo em {location}, no dia {date}, às {time}.',
                'positions' => ['cooperador', 'anciao', 'diacono'],
                'layout' => 'list_only',
                'group' => 'batismo',
            ],
            'reuniao-mocidade' => [
                'paragraph' => 'Convidamos a mocidade para a reunião em {location}, {date} às {time}.',
                'positions' => ['cooperador-jovens', 'anciao'],
                'layout' => 'list_only',
                'group' => 'mocidade',
            ],
            'culto-jovens-12' => [
                'paragraph' => 'Culto para jovens acima de 12 anos em {location}, {date} às {time}.',
                'positions' => ['cooperador-jovens'],
                'layout' => 'list_only',
                'group' => 'jovens',
            ],
            'ensaio-regional' => [
                'paragraph' => 'Ensaio regional em {location}, {date} às {time}.',
                'positions' => ['encarregado-regional', 'encarregado-local'],
                'layout' => 'list_only',
                'group' => 'ensaio',
            ],
            'rco' => [
                'paragraph' => '',
                'positions' => ['anciao', 'cooperador', 'diacono'],
                'layout' => 'intro_list',
                'group' => 'rco',
            ],
            'rma' => [
                'paragraph' => '',
                'positions' => ['anciao', 'cooperador', 'presidente', 'secretario'],
                'layout' => 'intro_list',
                'group' => 'rma',
            ],
            // Textos alinhados ao PDF modelo 2026-10-15 LISTA_DE_BATISMOS_REA_R00
            'reuniao-organistas' => [
                'paragraph' => 'No dia {day_month}, {weekday}, às {time} horas, haverá essa reunião na casa de oração {location}, para as irmãs organistas e candidatas de toda a Regional {regional}. As organistas que tocam nas Reuniões de Jovens e Menores devem priorizar participar do Ensaio para seu aprimoramento.',
                'positions' => ['encarregado-regional', 'encarregado-local'],
                'layout' => 'narrative',
                'group' => 'reunioes-regionais',
            ],
            'reuniao-obra-piedade' => [
                'paragraph' => 'No dia {day_month}, {weekday}, às {time} horas, haverá essa reunião de Ensinamentos da Obra da Piedade na casa de oração {location}, devendo comparecer os irmãos Anciães, Diáconos, Cooperadores do Ofício Ministerial, Cooperadores de Jovens e Menores, irmãs da Obra da Piedade e Colaboradores da Secretaria da Piedade de todos os municípios que compõem a regional.',
                'positions' => ['anciao', 'cooperador'],
                'layout' => 'narrative',
                'group' => 'reunioes-regionais',
            ],
            'ra' => [
                'paragraph' => 'No dia {day_month}, {weekday}, às {time} horas, haverá essa reunião na casa de oração {location}, para os irmãos Anciães, Diáconos, Cooperadores do Ofício Ministerial, Cooperadores de Jovens e Menores, Administradores Estatutários, Conselho Fiscal e Colaboradores, de todos os municípios que compõem a Regional.',
                'positions' => ['presidente', 'secretario', 'anciao'],
                'layout' => 'narrative',
                'group' => 'reunioes-regionais',
            ],
            'rrm' => [
                'paragraph' => "No dia {day_month}, {weekday}, haverá essa reunião na casa de oração {location},\n• Às 15:00 horas com o Conselho de Anciães mais Antigos da Regional (somente os convocados);\n• Às 17:30 horas com os Anciães e Diáconos;\n• Às 19:30 horas com os Anciães, Diáconos, Cooperadores do Ofício Ministerial, Cooperadores de Jovens e Menores, Administradores Estatutários e Conselho Fiscal, de todos os municípios que compõem a Regional.",
                'positions' => ['anciao', 'cooperador', 'diacono'],
                'layout' => 'narrative',
                'group' => 'reunioes-regionais',
            ],
            'rga' => [
                'paragraph' => 'No dia {day_month}, {weekday}, às {time} horas, haverá essa reunião na casa de oração {location}, para os irmãos Anciães, Diáconos, Cooperadores do Ofício Ministerial, Cooperadores de Jovens e Menores, Administradores Estatutários, Conselho Fiscal e Colaboradores, de todos os municípios que compõem a Regional.',
                'positions' => ['presidente', 'secretario', 'tesoureiro'],
                'layout' => 'narrative',
                'group' => 'reunioes-regionais',
            ],
            'santa-ceia' => [
                'paragraph' => 'Santa Ceia em {location}, {date} às {time}.',
                'positions' => ['anciao', 'cooperador'],
                'layout' => 'list_only',
                'group' => 'santa-ceia',
            ],
        ];

        foreach ($presets as $slug => $cfg) {
            $typeId = EventType::query()->where('slug', $slug)->value('id');
            if (! $typeId) {
                continue;
            }

            $preset = EventTypeAudiencePreset::query()->updateOrCreate(
                ['event_type_id' => $typeId],
                [
                    'invitation_paragraph' => $cfg['paragraph'],
                    'report_layout' => $cfg['layout'],
                    'meta' => [
                        'seeded' => true,
                        'report_group' => $cfg['group'] ?? null,
                    ],
                ],
            );

            $preset->positions()->delete();
            foreach ($cfg['positions'] as $positionSlug) {
                $preset->positions()->create(['position_slug' => $positionSlug]);
            }
        }
    }
}
