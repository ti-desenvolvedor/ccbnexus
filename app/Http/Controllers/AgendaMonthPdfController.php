<?php

namespace App\Http\Controllers;

use App\Exceptions\Agenda\AgendaRegionalContextRequired;
use App\Exceptions\Agenda\InvalidAgendaReadCriteria;
use App\Models\Event;
use App\Models\Regional;
use App\Services\Agenda\AgendaYearBook;
use App\Services\Agenda\RecurrenceHorizon;
use App\Services\OrganizationalContextService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgendaMonthPdfController extends Controller
{
    public function __invoke(
        Request $request,
        AgendaYearBook $book,
        RecurrenceHorizon $horizon,
        OrganizationalContextService $context,
    ): Response {
        $user = $request->user();
        abort_unless($user && $user->can('viewAny', Event::class), 403);

        try {
            $cursor = Carbon::parse((string) $request->query('cursor', now()->toDateString()), config('app.timezone'));
        } catch (\Throwable) {
            return response()->view('reports.agenda.invalid-period', [], 422);
        }

        $type = $request->query('event_type_id');
        $eventTypeId = is_numeric($type) ? (int) $type : null;
        $regionalId = $context->activeRegionalId();
        $regional = $regionalId
            ? Regional::query()->find($regionalId, ['id', 'name', 'slug'])
            : null;

        $horizon->extendOpenSeries($regionalId);

        try {
            $document = $book->compose(
                user: $user,
                year: (int) $cursor->year,
                search: (string) $request->query('search', ''),
                eventTypeId: $eventTypeId,
                channel: (string) $request->query('channel', ''),
                status: (string) $request->query('status', ''),
            );
        } catch (AgendaRegionalContextRequired) {
            return response()->view('reports.agenda.context-required', [], 422);
        } catch (InvalidAgendaReadCriteria $e) {
            return response()->view('reports.agenda.invalid-period', ['message' => $e->getMessage()], 422);
        }

        if ($regional) {
            $document['regionalName'] = $regional->name;
        }

        ini_set('memory_limit', '512M');

        $pdf = Pdf::loadView('reports.agenda.year-book', $document)
            ->setPaper('a4', 'portrait');

        $name = sprintf(
            'agenda-%s-%d.pdf',
            $regional?->slug ?: 'regional',
            $document['year'],
        );

        return $pdf->download($name);
    }
}
