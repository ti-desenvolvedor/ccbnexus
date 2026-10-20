<?php

namespace App\Http\Controllers;

use App\Models\BaptismListReport;
use App\Services\BaptismListDocumentBlocks;
use App\Services\BaptismListDocxExporter;
use Symfony\Component\HttpFoundation\Response;

class BaptismListDocxController extends Controller
{
    public function __invoke(BaptismListReport $report, BaptismListDocxExporter $exporter): Response
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('visualizar_relatorios')), 403);

        if (! $user->isSuperAdmin()) {
            abort_unless(in_array($report->regional_id, $user->accessibleRegionalIds(), true), 403);
        }

        $sections = BaptismListDocumentBlocks::orderedSections($report);
        $binary = $exporter->binary($report, $sections);

        $name = sprintf(
            'lista-batismos-%s-R%02d.docx',
            $report->issue_date->format('Y-m-d'),
            $report->revision
        );

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }
}
