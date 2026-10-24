<?php

namespace App\Http\Controllers;

use App\Models\BaptismListReport;
use App\Services\BaptismListPdfRenderer;
use Symfony\Component\HttpFoundation\Response;

class BaptismListPdfController extends Controller
{
    public function __invoke(BaptismListReport $report, BaptismListPdfRenderer $renderer): Response
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('visualizar_relatorios')), 403);

        if (! $user->isSuperAdmin()) {
            abort_unless(in_array($report->regional_id, $user->accessibleRegionalIds(), true), 403);
        }

        $report->load(['items', 'regional']);

        $name = sprintf(
            'lista-batismos-%s-R%02d.pdf',
            $report->issue_date->format('Y-m-d'),
            $report->revision
        );

        return response($renderer->binary($report), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }
}
