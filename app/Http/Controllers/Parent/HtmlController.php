<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\Html\HtmlReportService;
use Inertia\Inertia;
use Inertia\Response;

class HtmlController extends Controller
{
    public function index(HtmlReportService $reports): Response
    {
        return Inertia::render('html/parent/index', [
            'summary' => $reports->parentSummary(request()->user()),
        ]);
    }
}
