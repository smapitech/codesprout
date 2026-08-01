<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\Rewards\ProgressReportService;
use Inertia\Inertia;
use Inertia\Response;

class ProgressController extends Controller
{
    public function index(ProgressReportService $reports): Response
    {
        $parent = request()->user();
        abort_unless($parent, 403);

        return Inertia::render('parent/progress/index', $reports->parentSummary($parent));
    }
}
