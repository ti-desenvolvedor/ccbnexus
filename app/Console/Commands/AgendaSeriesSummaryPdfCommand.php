<?php

namespace App\Console\Commands;

use App\Models\Regional;
use App\Models\User;
use App\Services\Agenda\AgendaSeriesSummary;
use App\Services\Agenda\AgendaYearBook;
use App\Services\OrganizationalContextService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class AgendaSeriesSummaryPdfCommand extends Command
{
    protected $signature = 'nexus:agenda-resumo-pdf
                            {--year=2026 : Ano da agenda}
                            {--output= : Caminho absoluto do PDF}
                            {--resumo : Só a listagem por séries, sem o caderno}';

    protected $description = 'Grava o PDF da agenda (índice, séries, meses, anotações e capa final). --resumo grava só o resumo.';

    public function handle(
        AgendaYearBook $book,
        AgendaSeriesSummary $summary,
        OrganizationalContextService $context,
    ): int {
        $year = (int) $this->option('year');

        if ($this->option('resumo')) {
            return $this->writeSummary($summary, $year);
        }

        $regional = Regional::query()->where('slug', 'embu-das-artes')->first()
            ?? Regional::query()->where('is_active', true)->orderBy('id')->first();
        $user = User::query()->where('is_super_admin', true)->first();
        if (! $regional || ! $user) {
            $this->error('Regional ou super-admin em falta para gerar o caderno.');

            return self::FAILURE;
        }

        auth()->login($user);
        $context->setActive($regional->id, null, null);

        $document = $book->compose($user, $year);
        $document['regionalName'] = $regional->name;

        $output = (string) ($this->option('output') ?: storage_path('app/agenda/agenda-'.$regional->slug.'-'.$year.'.pdf'));

        return $this->save('reports.agenda.year-book', $document, $output, $this->pageOrder($document['pages']));
    }

    protected function writeSummary(AgendaSeriesSummary $summary, int $year): int
    {
        $document = $summary->compose($year);
        $output = (string) ($this->option('output') ?: storage_path('app/agenda/agenda-resumo-embu-das-artes-'.$year.'.pdf'));

        return $this->save(
            'reports.agenda.series-summary',
            $document,
            $output,
            $document['seriesCount'].' séries, '.$document['eventCount'].' eventos.',
        );
    }

    /**
     * @param  array<string, mixed>  $document
     */
    protected function save(string $view, array $document, string $output, string $note): int
    {
        $dir = dirname($output);
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            $this->error('Não foi possível criar a pasta de saída.');

            return self::FAILURE;
        }

        ini_set('memory_limit', '512M');

        Pdf::loadView($view, $document)
            ->setPaper('a4', 'portrait')
            ->save($output);

        $this->info($output);
        $this->line($note);

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     */
    protected function pageOrder(array $pages): string
    {
        $parts = [];
        foreach ($pages as $page) {
            $kind = (string) ($page['kind'] ?? '');
            $parts[] = match ($kind) {
                'cover' => 'capa',
                'index' => 'índice',
                'listing' => 'listagem'.(! empty($page['continued']) ? ' (cont.)' : ''),
                'month' => (string) ($page['name'] ?? 'mês').(! empty($page['continued']) ? ' (cont.)' : ''),
                'notes' => 'anotações',
                'end-cover' => 'capa final',
                default => $kind,
            };
        }

        return count($pages).' páginas: '.implode(' → ', $parts);
    }
}
