<?php

namespace App\Http\Controllers;

use App\Models\Regional;
use App\Services\MarkingsReportBuilder;
use App\Services\OrganizationalContextService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

class MarkingsReportExportController extends Controller
{
    public function __invoke(
        Request $request,
        string $format,
        MarkingsReportBuilder $builder,
        OrganizationalContextService $context,
    ): Response {
        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);

        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('visualizar_relatorios')), 403);

        $requested = array_values(array_filter(explode(',', (string) $request->query('slugs', ''))));
        if ($requested === []) {
            $requested = array_keys(MarkingsReportBuilder::DEFAULT_TYPES);
        }
        [$defaults, $extras] = $builder->splitSlugs($requested);
        [$from, $to] = $builder->period(
            $request->query('date_from'),
            $request->query('date_to'),
        );
        $status = (string) $request->query('filter', 'all');
        if (! in_array($status, ['all', 'missing', 'ok'], true)) {
            $status = 'all';
        }

        $regionalId = $context->activeRegionalId();
        $report = $builder->report($user, $regionalId, $defaults, $extras, $from, $to, $status);
        $regionalName = $regionalId
            ? Regional::query()->whereKey($regionalId)->value('name')
            : null;
        $filename = sprintf('marcacoes-%s-a-%s', $from->format('Y-m-d'), $to->format('Y-m-d'));

        if ($format === 'pdf') {
            return Pdf::loadView('reports.markings.pdf', [
                'groups' => $report['groups'],
                'from' => $from,
                'to' => $to,
                'regionalName' => $regionalName,
            ])->setPaper('a4', 'portrait')->download($filename.'.pdf');
        }

        return response()->streamDownload(function () use ($report, $from, $to, $regionalName) {
            $sheet = new Spreadsheet;
            $ws = $sheet->getActiveSheet();
            $ws->setTitle('Marcações');
            $ws->setCellValue('A1', 'Relatório de Marcações');
            $ws->setCellValue('A2', trim(sprintf(
                '%s a %s%s',
                $from->format('d/m/Y'),
                $to->format('d/m/Y'),
                $regionalName ? ' · '.$regionalName : '',
            )));
            $headers = ['Data', 'Evento', 'Local', 'Quem atende', 'Ministério'];
            $ws->fromArray($headers, null, 'A4');
            $ws->getStyle('A4:E4')->getFont()->setBold(true);

            $line = 5;
            foreach ($report['groups'] as $group) {
                $ws->setCellValue('A'.$line, $group['name']);
                $ws->mergeCells('A'.$line.':E'.$line);
                $ws->getStyle('A'.$line)->getFont()->setBold(true);
                $line++;
                foreach ($group['rows'] as $row) {
                    $ws->setCellValue('A'.$line, $row['when']);
                    $ws->setCellValue('B'.$line, $row['type_name']);
                    $ws->setCellValue('C'.$line, $row['place']);
                    $ws->setCellValue('D'.$line, $row['attendant']);
                    $ws->setCellValue('E'.$line, $row['ministry']);
                    if ($row['attendant'] === '') {
                        $ws->getStyle('D'.$line)->getBorders()->getBottom()
                            ->setBorderStyle(Border::BORDER_THIN);
                        $ws->getRowDimension($line)->setRowHeight(22);
                    }
                    $line++;
                }
            }

            foreach (range('A', 'E') as $column) {
                $ws->getColumnDimension($column)->setAutoSize(true);
            }
            $ws->getColumnDimension('D')->setWidth(36);

            (new Xlsx($sheet))->save('php://output');
        }, $filename.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
