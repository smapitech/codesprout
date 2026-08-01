<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Services\Typing\TypingReportService;
use Inertia\Inertia;
use Inertia\Response;

class TypingController extends Controller
{
    public function index(TypingReportService $reports): Response
    {
        return Inertia::render('typing/parent/index', [
            'results' => $reports->parentRows(request()->user()),
        ]);
    }
}
