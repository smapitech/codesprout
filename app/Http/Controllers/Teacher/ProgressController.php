<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Rewards\ProgressReportService;
use Inertia\Inertia;
use Inertia\Response;

class ProgressController extends Controller
{
    public function index(ProgressReportService $reports): Response
    {
        $teacher = request()->user();
        abort_unless($teacher, 403);

        return Inertia::render('teacher/progress/index', $reports->teacherClassSummary($teacher));
    }
}
