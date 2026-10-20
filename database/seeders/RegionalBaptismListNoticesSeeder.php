<?php

namespace Database\Seeders;

use App\Models\Regional;
use App\Models\RegionalNotice;
use Illuminate\Database\Seeder;

/**
 * Avisos permanentes do boletim (Lista de Batismos), alinhados ao modelo REA R00.
 */
class RegionalBaptismListNoticesSeeder extends Seeder
{
    public function run(): void
    {
        $regional = Regional::query()->where('slug', 'embu-das-artes')->first();
        if (! $regional) {
            $this->command?->warn('Regional embu-das-artes ausente — avisos do boletim não semeados.');

            return;
        }

        $notices = [
            [
                'seed_key' => 'alerta-halloween',
                'title' => 'Alerta à irmandade - Halloween',
                'body' => 'Lembramos à cara irmandade a Circular 206/2024 que adverte quanto a não participação em festas do dia das bruxas (Halloween), quer para a irmandade como também seus filhos, e não nos envolvermos com esses festejos, onde pessoas usam fantasias que sugerem a feitiçaria, a qual é condenada pela Palavra de Deus.',
                'category' => 'circular',
                'sort_order' => 10,
            ],
            [
                'seed_key' => 'informacao-irmandade',
                'title' => 'Informação à Irmandade',
                'body' => 'Reiterando nosso compromisso com a cara irmandade, lembramos que o Conselho dos Anciães mais Antigos, sempre zelando pela constância e firmeza de todos na fé em Cristo Jesus nosso Senhor, tem feito publicar em nosso site, cartas, avisos e circulares, orientativos a quem quer que seja, com o objetivo de instruir sobre assuntos da atualidade, à luz da palavra de Deus. Conselho dos Anciães mais antigos',
                'category' => 'geral',
                'sort_order' => 20,
            ],
            [
                'seed_key' => 'esclarecimento-eleicoes',
                'title' => 'Esclarecimento sobre Eleições',
                'body' => 'A Congregação Cristã no Brasil tem como princípio básico propagar o Evangelho de Jesus Cristo. Por intermédio deste comunicado manifesta sua posição de neutralidade com relação a eleição para cargos públicos e ou políticos. Como instituição somos apolíticos e apartidários, porém, é nosso dever cumprir a obrigação do voto, baseado na própria consciência, observando os valores cristãos, conforme instruídos na Palavra de Deus. Consta em nosso site a circular com maiores esclarecimentos sobre o assunto. Conselho dos Anciães mais antigos.',
                'category' => 'circular',
                'sort_order' => 30,
            ],
        ];

        foreach ($notices as $row) {
            $existing = RegionalNotice::query()
                ->where('regional_id', $regional->id)
                ->where('meta->seed_key', $row['seed_key'])
                ->first();

            $payload = [
                'regional_id' => $regional->id,
                'title' => $row['title'],
                'body' => $row['body'],
                'category' => $row['category'],
                'valid_from' => '2020-01-01',
                'valid_until' => '2099-12-31',
                'is_active' => true,
                'is_standing' => true,
                'sort_order' => $row['sort_order'],
                'meta' => [
                    'seed_key' => $row['seed_key'],
                    'source' => 'lista-batismos-modelo-r00',
                ],
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                RegionalNotice::query()->create($payload);
            }
        }

        $this->command?->info('Avisos permanentes do boletim (Halloween, Informação, Eleições) semeados para Embu das Artes.');
    }
}
