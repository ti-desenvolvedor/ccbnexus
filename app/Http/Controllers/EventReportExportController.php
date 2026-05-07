<?php

namespace App\Http\Controllers;

use App\Services\EventExportService;
use Illuminate\Http\Request;

class EventReportExportController extends Controller
{
    public function __invoke(Request $request, EventExportService $export)
    {
        return $export->csvForUser($request->user());
    }
}
