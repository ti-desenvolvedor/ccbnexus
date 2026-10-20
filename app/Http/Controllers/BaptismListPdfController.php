<?php

namespace App\Http\Controllers;

use App\Models\BaptismListReport;
use App\Services\BaptismListReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class BaptismListPdfController extends Controller
{
    public function __invoke(BaptismListReport $report): Response
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('visualizar_relatorios')), 403);

        if (! $user->isSuperAdmin()) {
            abort_unless(in_array($report->regional_id, $user->accessibleRegionalIds(), true), 403);
        }

        $report->load(['items', 'regional']);

        $sections = $report->items
            ->where('is_visible', true)
            ->groupBy('section_key')
            ->all();

        // Garante ordem estável das secções do layout.
        $ordered = [];
        foreach (array_keys(BaptismListReportBuilder::SECTION_ORDER) as $key) {
            if (isset($sections[$key])) {
                $ordered[$key] = $sections[$key];
            }
        }
        foreach ($sections as $key => $items) {
            if (! isset($ordered[$key])) {
                $ordered[$key] = $items;
            }
        }

        $pdf = Pdf::loadView('reports.baptism-list.pdf', [
            'report' => $report,
            'sections' => $ordered,
        ])->setPaper('a4');

        // Modelo REA usa Arial Narrow; libera a pasta de fontes do Windows quando existir.
        $windowsFonts = 'C:\\Windows\\Fonts';
        if (is_dir($windowsFonts)) {
            $dompdf = $pdf->getDomPDF();
            $options = $dompdf->getOptions();
            $chroot = $options->getChroot();
            $roots = is_array($chroot) ? $chroot : array_filter([$chroot]);
            $roots[] = $windowsFonts;
            $options->setChroot(array_values(array_unique($roots)));
        }

        $name = sprintf(
            'lista-batismos-%s-R%02d.pdf',
            $report->issue_date->format('Y-m-d'),
            $report->revision
        );

        return $pdf->download($name);
    }
}
