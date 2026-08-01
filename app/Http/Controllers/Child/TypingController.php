<?php

namespace App\Http\Controllers\Child;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Typing\TypingEventBatchRequest;
use App\Http\Requests\Typing\TypingStartRequest;
use App\Models\TypingExercise;
use App\Models\TypingSession;
use App\Services\Typing\TypingReportService;
use App\Services\Typing\TypingSessionService;
use Inertia\Inertia;
use Inertia\Response;

class TypingController extends Controller
{
    public function index(TypingReportService $reports): Response
    {
        $exercises = TypingExercise::query()
            ->with('currentVersion.difficultyProfile')
            ->where('status', ContentStatus::Published)
            ->orderBy('title')
            ->limit(12)
            ->get()
            ->map(fn (TypingExercise $exercise): array => [
                'slug' => $exercise->slug,
                'title' => $exercise->title,
                'type' => $exercise->exercise_type->label(),
                'difficulty' => $exercise->currentVersion?->difficultyProfile?->name,
                'instructions' => $exercise->child_instructions,
                'startHref' => route('child.typing.start', $exercise, absolute: false),
            ]);

        return Inertia::render('child/typing/index', [
            'summary' => $reports->childSummary(request()->user()),
            'exercises' => $exercises,
        ]);
    }

    public function start(TypingStartRequest $request, TypingExercise $typing, TypingSessionService $service)
    {
        abort_unless($typing->status === ContentStatus::Published && $typing->currentVersion, 404);
        $session = $service->start($typing->currentVersion, $request->user(), $request->validated());

        return redirect()->route('child.typing.play', $session);
    }

    public function play(TypingSession $session, TypingSessionService $service): Response
    {
        return Inertia::render('child/typing/player', [
            'payload' => $service->payload($session, request()->user()),
            'actions' => [
                'batch' => route('child.typing.batch', $session, absolute: false),
                'pause' => route('child.typing.pause', $session, absolute: false),
                'resume' => route('child.typing.resume', $session, absolute: false),
                'complete' => route('child.typing.complete', $session, absolute: false),
                'leave' => route('child.typing.index', absolute: false),
            ],
        ]);
    }

    public function batch(TypingEventBatchRequest $request, TypingSession $session, TypingSessionService $service)
    {
        $batch = $service->recordBatch($session, $request->user(), $request->validated());

        return response()->json(['status' => 'saved', 'batch' => $batch->batch_uuid]);
    }

    public function pause(TypingSession $session, TypingSessionService $service)
    {
        $service->pause($session, request()->user());

        return back();
    }

    public function resume(TypingSession $session, TypingSessionService $service)
    {
        $service->resume($session, request()->user(), request()->integer('state_version') ?: null);

        return back();
    }

    public function complete(TypingSession $session, TypingSessionService $service)
    {
        $service->complete($session, request()->user(), request()->header('Idempotency-Key'));

        return back();
    }
}
