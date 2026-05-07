<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventExportService
{
    public function csvForUser(User $user): StreamedResponse
    {
        if (! $user->can('visualizar_relatorios')) {
            abort(403);
        }

        $query = Event::query()->with(['regional', 'eventType'])->orderBy('starts_at');

        if (! $user->isSuperAdmin()) {
            $ids = $user->accessibleRegionalIds();
            $query->where(function ($q) use ($ids) {
                $q->whereIn('regional_id', $ids)->orWhereNull('regional_id');
            });
        }

        $filename = 'eventos-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'titulo', 'regional', 'tipo', 'inicio', 'fim', 'estado']);
            $query->chunk(200, function ($events) use ($out) {
                foreach ($events as $e) {
                    fputcsv($out, [
                        $e->id,
                        $e->title,
                        $e->regional?->name,
                        $e->eventType?->name,
                        $e->starts_at?->toIso8601String(),
                        $e->ends_at?->toIso8601String(),
                        $e->status,
                    ]);
                }
            });
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
