<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\Games\GameReportService;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function index(GameReportService $reportService): Response
    {
        return Inertia::render('parent/games/index', [
            'results' => $reportService->parentResults(request()->user()),
        ]);
    }
}
