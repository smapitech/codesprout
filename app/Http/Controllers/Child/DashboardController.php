<?php

namespace App\Http\Controllers\Child;

use App\Http\Controllers\Controller;
use App\Services\ChildDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(ChildDashboardService $dashboardService): Response
    {
        $child = request()->user();
        abort_unless($child, 403);

        return Inertia::render('child/dashboard', $dashboardService->build($child));
    }
}
