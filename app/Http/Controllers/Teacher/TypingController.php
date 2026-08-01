<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\TypingExercise;
use App\Services\Typing\TypingReportService;
use App\Services\Typing\TypingSessionService;
use Inertia\Inertia;
use Inertia\Response;

class TypingController extends Controller
{
    public function index(): Response
    {
        $exercises = TypingExercise::query()
            ->with(['currentVersion.difficultyProfile'])
            ->where('status', ContentStatus::Published)
            ->orderBy('title')
            ->get()
            ->map(fn (TypingExercise $exercise): array => [
                'slug' => $exercise->slug,
                'title' => $exercise->title,
                'type' => $exercise->exercise_type->label(),
                'difficulty' => $exercise->currentVersion?->difficultyProfile?->name,
                'instructions' => $exercise->child_instructions,
                'previewHref' => route('teacher.typing.preview', $exercise, absolute: false),
            ]);

        return Inertia::render('typing/teacher/index', ['exercises' => $exercises]);
    }

    public function results(TypingReportService $reports): Response
    {
        return Inertia::render('typing/teacher/results', [
            'results' => $reports->teacherRows(request()->user()),
        ]);
    }

    public function preview(TypingExercise $typing, TypingSessionService $service): Response
    {
        abort_unless($typing->status === ContentStatus::Published && $typing->currentVersion, 404);
        $session = $service->preview($typing->currentVersion, request()->user());

        return Inertia::render('typing/teacher/preview', [
            'payload' => $service->payload($session, request()->user()),
            'banner' => 'Teacher Preview - no learner progress or rewards will be created.',
        ]);
    }
}
